<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class PostRepositoryTest extends TestCase
{
    private PostRepositoryInterface $repository;
    private PostInterfaceFactory $postFactory;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(PostRepositoryInterface::class);
        $this->postFactory = $objectManager->get(PostInterfaceFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_and_load_roundtrip(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Hello World')
            ->setUrlKey('hello-world')
            ->setContent('<p>Body</p>');

        $saved = $this->repository->save($post);
        self::assertNotNull($saved->getPostId());

        $loaded = $this->repository->getById((int) $saved->getPostId());
        self::assertSame('Hello World', $loaded->getTitle());
        self::assertSame('hello-world', $loaded->getUrlKey());
        self::assertSame('<p>Body</p>', $loaded->getContent());
    }

    public function test_save_persists_store_links(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Scoped')->setUrlKey('scoped')->setStoreIds([1]);

        $saved = $this->repository->save($post);
        $loaded = $this->repository->getById((int) $saved->getPostId());

        self::assertSame([1], $loaded->getStoreIds());
    }

    public function test_save_persists_category_and_tag_links(): void
    {
        $connection = $this->resource->getConnection();
        $connection->insert(
            $this->resource->getTableName('mageos_blog_category'),
            ['url_key' => 'cat-a', 'title' => 'Cat A']
        );
        $categoryId = (int) $connection->lastInsertId();

        $connection->insert(
            $this->resource->getTableName('mageos_blog_tag'),
            ['url_key' => 'tag-a', 'title' => 'Tag A']
        );
        $tagId = (int) $connection->lastInsertId();

        $post = $this->postFactory->create();
        $post->setTitle('Taxonomized')
            ->setUrlKey('taxonomized')
            ->setCategoryIds([$categoryId])
            ->setTagIds([$tagId]);

        $saved = $this->repository->save($post);
        $loaded = $this->repository->getById((int) $saved->getPostId());

        self::assertSame([$categoryId], $loaded->getCategoryIds());
        self::assertSame([$tagId], $loaded->getTagIds());
    }

    public function test_get_by_url_key_respects_store_scope(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Only Store 1')->setUrlKey('only-store-1')->setStoreIds([1]);
        $this->repository->save($post);

        $found = $this->repository->getByUrlKey('only-store-1', 1);
        self::assertSame('Only Store 1', $found->getTitle());

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByUrlKey('only-store-1', 2);
    }

    public function test_get_by_url_key_finds_all_stores_post(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('All Stores')->setUrlKey('all-stores')->setStoreIds([0]);
        $this->repository->save($post);

        $found = $this->repository->getByUrlKey('all-stores', 99);
        self::assertSame('All Stores', $found->getTitle());
    }

    public function test_delete_removes_pivot_rows(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('To Delete')->setUrlKey('to-delete')->setStoreIds([1]);
        $saved = $this->repository->save($post);
        $postId = (int) $saved->getPostId();

        self::assertTrue($this->repository->deleteById($postId));

        $connection = $this->resource->getConnection();
        $storeCount = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('mageos_blog_post_store'), ['COUNT(*)'])
                ->where('post_id = ?', $postId)
        );
        self::assertSame(0, (int) $storeCount);
    }

    public function test_get_list_filters_by_url_key(): void
    {
        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $post = $this->postFactory->create();
            $post->setTitle(ucfirst($slug))->setUrlKey('list-' . $slug);
            $this->repository->save($post);
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(PostInterface::URL_KEY, 'list-%', 'like')
            ->create();

        $results = $this->repository->getList($criteria);
        self::assertGreaterThanOrEqual(3, $results->getTotalCount());
    }

    public function test_get_by_id_throws_on_missing(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getById(9999999);
    }
}

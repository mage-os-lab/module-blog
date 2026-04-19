<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class TagRepositoryTest extends TestCase
{
    private TagRepositoryInterface $repository;
    private TagInterfaceFactory $tagFactory;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(TagRepositoryInterface::class);
        $this->tagFactory = $objectManager->get(TagInterfaceFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_and_load_roundtrip(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('Magento')
            ->setUrlKey('magento-tag')
            ->setDescription('Magento related');

        $saved = $this->repository->save($tag);
        self::assertNotNull($saved->getTagId());

        $loaded = $this->repository->getById((int) $saved->getTagId());
        self::assertSame('Magento', $loaded->getTitle());
        self::assertSame('magento-tag', $loaded->getUrlKey());
        self::assertSame('Magento related', $loaded->getDescription());
    }

    public function test_save_persists_store_links(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('Scoped Tag')
            ->setUrlKey('scoped-tag')
            ->setStoreIds([1]);

        $saved = $this->repository->save($tag);
        $loaded = $this->repository->getById((int) $saved->getTagId());

        self::assertSame([1], $loaded->getStoreIds());
    }

    public function test_get_by_url_key_respects_store_scope(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('Store 1 Tag')
            ->setUrlKey('store-1-tag')
            ->setStoreIds([1]);
        $this->repository->save($tag);

        $found = $this->repository->getByUrlKey('store-1-tag', 1);
        self::assertSame('Store 1 Tag', $found->getTitle());

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByUrlKey('store-1-tag', 2);
    }

    public function test_get_by_url_key_finds_all_stores_tag(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('All Stores Tag')
            ->setUrlKey('all-stores-tag')
            ->setStoreIds([0]);
        $this->repository->save($tag);

        $found = $this->repository->getByUrlKey('all-stores-tag', 99);
        self::assertSame('All Stores Tag', $found->getTitle());
    }

    public function test_delete_removes_pivot_rows(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('To Delete')
            ->setUrlKey('to-delete-tag')
            ->setStoreIds([1]);
        $saved = $this->repository->save($tag);
        $tagId = (int) $saved->getTagId();

        self::assertTrue($this->repository->deleteById($tagId));

        $connection = $this->resource->getConnection();
        $storeCount = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('mageos_blog_tag_store'), ['COUNT(*)'])
                ->where('tag_id = ?', $tagId)
        );
        self::assertSame(0, (int) $storeCount);
    }

    public function test_get_list_filters_by_url_key(): void
    {
        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $tag = $this->tagFactory->create();
            $tag->setTitle(ucfirst($slug))->setUrlKey('tag-list-' . $slug);
            $this->repository->save($tag);
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(TagInterface::URL_KEY, 'tag-list-%', 'like')
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

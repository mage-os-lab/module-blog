<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use PHPUnit\Framework\TestCase;

final class CategoryRepositoryTest extends TestCase
{
    private CategoryRepositoryInterface $repository;
    private CategoryInterfaceFactory $categoryFactory;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(CategoryRepositoryInterface::class);
        $this->categoryFactory = $objectManager->get(CategoryInterfaceFactory::class);
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_and_load_roundtrip(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('News')
            ->setUrlKey('news')
            ->setDescription('Latest news');

        $saved = $this->repository->save($category);
        self::assertNotNull($saved->getCategoryId());

        $loaded = $this->repository->getById((int) $saved->getCategoryId());
        self::assertSame('News', $loaded->getTitle());
        self::assertSame('news', $loaded->getUrlKey());
        self::assertSame('Latest news', $loaded->getDescription());
    }

    public function test_save_persists_store_links(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('Scoped Category')
            ->setUrlKey('scoped-category')
            ->setStoreIds([1]);

        $saved = $this->repository->save($category);
        $loaded = $this->repository->getById((int) $saved->getCategoryId());

        self::assertSame([1], $loaded->getStoreIds());
    }

    public function test_get_by_url_key_respects_store_scope(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('Store 1 Category')
            ->setUrlKey('store-1-category')
            ->setStoreIds([1]);
        $this->repository->save($category);

        $found = $this->repository->getByUrlKey('store-1-category', 1);
        self::assertSame('Store 1 Category', $found->getTitle());

        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByUrlKey('store-1-category', 2);
    }

    public function test_get_by_url_key_finds_all_stores_category(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('All Stores Category')
            ->setUrlKey('all-stores-category')
            ->setStoreIds([0]);
        $this->repository->save($category);

        $found = $this->repository->getByUrlKey('all-stores-category', 99);
        self::assertSame('All Stores Category', $found->getTitle());
    }

    public function test_delete_removes_pivot_rows(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('To Delete')
            ->setUrlKey('to-delete-category')
            ->setStoreIds([1]);
        $saved = $this->repository->save($category);
        $categoryId = (int) $saved->getCategoryId();

        self::assertTrue($this->repository->deleteById($categoryId));

        $connection = $this->resource->getConnection();
        $storeCount = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('mageos_blog_category_store'), ['COUNT(*)'])
                ->where('category_id = ?', $categoryId)
        );
        self::assertSame(0, (int) $storeCount);
    }

    public function test_get_list_filters_by_url_key(): void
    {
        foreach (['alpha', 'beta', 'gamma'] as $slug) {
            $category = $this->categoryFactory->create();
            $category->setTitle(ucfirst($slug))->setUrlKey('cat-list-' . $slug);
            $this->repository->save($category);
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter(CategoryInterface::URL_KEY, 'cat-list-%', 'like')
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

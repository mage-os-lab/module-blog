<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

final class CategoryUrlRewritePluginTest extends TestCase
{
    private CategoryRepositoryInterface $repository;
    private CategoryInterfaceFactory $categoryFactory;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(CategoryRepositoryInterface::class);
        $this->categoryFactory = $objectManager->get(CategoryInterfaceFactory::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_creates_url_rewrite_row(): void
    {
        $category = $this->categoryFactory->create();
        $category->setTitle('News')
            ->setUrlKey('news')
            ->setStoreIds([1]);
        $saved = $this->repository->save($category);

        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_CATEGORY)
                ->where('entity_id = ?', (int) $saved->getCategoryId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/category/news', $row['request_path']);
        self::assertStringContainsString('blog/category/view/id/', $row['target_path']);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

final class TagUrlRewritePluginTest extends TestCase
{
    private TagRepositoryInterface $repository;
    private TagInterfaceFactory $tagFactory;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(TagRepositoryInterface::class);
        $this->tagFactory = $objectManager->get(TagInterfaceFactory::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_creates_url_rewrite_row(): void
    {
        $tag = $this->tagFactory->create();
        $tag->setTitle('Magento')
            ->setUrlKey('magento')
            ->setStoreIds([1]);
        $saved = $this->repository->save($tag);

        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_TAG)
                ->where('entity_id = ?', (int) $saved->getTagId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/tag/magento', $row['request_path']);
        self::assertStringContainsString('blog/tag/view/id/', $row['target_path']);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

final class AuthorUrlRewritePluginTest extends TestCase
{
    private AuthorRepositoryInterface $repository;
    private AuthorInterfaceFactory $authorFactory;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(AuthorRepositoryInterface::class);
        $this->authorFactory = $objectManager->get(AuthorInterfaceFactory::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_creates_url_rewrite_row(): void
    {
        $author = $this->authorFactory->create();
        $author->setName('Jane Doe')
            ->setSlug('jane-doe');
        $saved = $this->repository->save($author);

        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_AUTHOR)
                ->where('entity_id = ?', (int) $saved->getAuthorId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/author/jane-doe', $row['request_path']);
        self::assertStringContainsString('blog/author/view/id/', $row['target_path']);
    }
}

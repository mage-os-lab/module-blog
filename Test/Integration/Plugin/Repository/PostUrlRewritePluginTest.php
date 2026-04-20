<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

final class PostUrlRewritePluginTest extends TestCase
{
    private PostRepositoryInterface $repository;
    private PostInterfaceFactory $postFactory;
    private ResourceConnection $resource;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->repository = $objectManager->get(PostRepositoryInterface::class);
        $this->postFactory = $objectManager->get(PostInterfaceFactory::class);
        $this->resource = $objectManager->get(ResourceConnection::class);
    }

    public function test_save_creates_url_rewrite_row(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Rewrite Test')
            ->setUrlKey('rewrite-test')
            ->setStoreIds([1]);
        $saved = $this->repository->save($post);

        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', (int) $saved->getPostId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/rewrite-test', $row['request_path']);
        self::assertStringContainsString('blog/post/view/id/', $row['target_path']);
    }

    public function test_slug_change_produces_301_redirect(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Original')
            ->setUrlKey('original-slug')
            ->setStoreIds([1]);
        $saved = $this->repository->save($post);

        $saved->setUrlKey('new-slug');
        $this->repository->save($saved);

        $connection = $this->resource->getConnection();
        $redirectRow = $connection->fetchRow(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', (int) $saved->getPostId())
                ->where('request_path = ?', 'blog/original-slug')
        );
        self::assertNotEmpty($redirectRow);
        self::assertSame('301', (string) $redirectRow['redirect_type']);
    }

    public function test_delete_removes_rewrite_rows(): void
    {
        $post = $this->postFactory->create();
        $post->setTitle('Doomed')
            ->setUrlKey('doomed')
            ->setStoreIds([1]);
        $saved = $this->repository->save($post);
        $postId = (int) $saved->getPostId();

        $this->repository->deleteById($postId);

        $connection = $this->resource->getConnection();
        $count = $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', $postId)
        );
        self::assertSame(0, (int) $count);
    }
}

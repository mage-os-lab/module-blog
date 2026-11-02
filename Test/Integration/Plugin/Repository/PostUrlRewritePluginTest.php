<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Plugin\Repository;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
final class PostUrlRewritePluginTest extends TestCase
{
    public function test_save_creates_url_rewrite_row(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Rewrite Test')
            ->setUrlKey('rewrite-test')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($post);

        $connection = $this->resource()->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', (int) $saved->getPostId())
        );
        self::assertNotEmpty($row);
        self::assertSame('blog/rewrite-test', $row['request_path']);
        self::assertStringContainsString('blog/post/view/id/', $row['target_path']);
    }

    public function test_all_store_views_covers_every_store(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('All Stores')
            ->setUrlKey('all-stores')
            ->setStoreIds([0]);
        $saved = $this->repository()->save($post);

        self::assertSame([0], $saved->getStoreIds());
        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getPostId()));
    }

    public function test_save_without_store_assignment_still_creates_rewrites(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Unassigned')
            ->setUrlKey('unassigned')
            ->setStoreIds([]);
        $saved = $this->repository()->save($post);

        self::assertSame($this->storeCount(), $this->countRewrites((int) $saved->getPostId()));
    }

    public function test_slug_change_produces_301_redirect(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Original')
            ->setUrlKey('original-slug')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($post);

        $saved->setUrlKey('new-slug');
        $this->repository()->save($saved);

        $connection = $this->resource()->getConnection();
        $redirectRow = $connection->fetchRow(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'))
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', (int) $saved->getPostId())
                ->where('request_path = ?', 'blog/original-slug')
        );
        self::assertNotEmpty($redirectRow);
        self::assertSame('301', (string) $redirectRow['redirect_type']);
    }

    public function test_removing_a_store_view_deletes_its_rewrite_rows(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Narrowed')
            ->setUrlKey('narrowed')
            ->setStoreIds([0]);
        $saved = $this->repository()->save($post);
        $postId = (int) $saved->getPostId();
        if ($this->storeCount() < 2) {
            self::markTestSkipped('Needs at least two store views.');
        }

        $saved->setStoreIds([1]);
        $this->repository()->save($saved);

        $connection = $this->resource()->getConnection();
        $storeIds = $connection->fetchCol(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['store_id'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', $postId)
        );
        self::assertSame([1], array_map('intval', $storeIds));
    }

    public function test_delete_removes_rewrite_rows(): void
    {
        $post = $this->postFactory()->create();
        $post->setTitle('Doomed')
            ->setUrlKey('doomed')
            ->setStoreIds([1]);
        $saved = $this->repository()->save($post);
        $postId = (int) $saved->getPostId();

        $this->repository()->deleteById($postId);

        $connection = $this->resource()->getConnection();
        $count = $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', $postId)
        );
        self::assertSame(0, (int) $count);
    }

    private function countRewrites(int $postId): int
    {
        $connection = $this->resource()->getConnection();

        return (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource()->getTableName('url_rewrite'), ['COUNT(*)'])
                ->where('entity_type = ?', UrlRewriteBuilder::ENTITY_TYPE_POST)
                ->where('entity_id = ?', $postId)
                ->where('redirect_type = ?', 0)
        );
    }

    private function storeCount(): int
    {
        return \count(Bootstrap::getObjectManager()->get(StoreManagerInterface::class)->getStores());
    }

    private function repository(): PostRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);
    }

    private function postFactory(): PostInterfaceFactory
    {
        return Bootstrap::getObjectManager()->get(PostInterfaceFactory::class);
    }

    private function resource(): ResourceConnection
    {
        return Bootstrap::getObjectManager()->get(ResourceConnection::class);
    }
}

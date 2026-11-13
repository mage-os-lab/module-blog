<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Repository;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Url\Permalink;
use MageOS\Blog\Model\Url\RedirectBuilder;
use MageOS\Blog\Model\Url\StaleStoreRewriteCleaner;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;
use MageOS\Blog\Plugin\Repository\Post\UrlRewritePlugin as PostPlugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/UrlRewriteFactoryStub.php';

/**
 * Redirects written when a URL key changes.
 */
final class UrlRewritePluginRedirectTest extends TestCase
{
    private InMemoryUrlStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new InMemoryUrlStorage();
    }

    #[Test]
    public function a_renamed_post_redirects_to_its_new_url_not_to_the_internal_route(): void
    {
        $this->savePost(7, 'old');
        $this->savePost(7, 'new');

        self::assertSame('blog/new', $this->redirect(1, 'blog/old')?->getTargetPath());
    }

    #[Test]
    public function the_redirect_survives_the_next_save(): void
    {
        $this->savePost(7, 'old');
        $this->savePost(7, 'new');
        $this->savePost(7, 'new');

        self::assertNotNull($this->redirect(1, 'blog/old'));
    }

    #[Test]
    public function every_earlier_url_points_straight_at_the_current_one(): void
    {
        $this->savePost(7, 'first');
        $this->savePost(7, 'second');
        $this->savePost(7, 'third');

        self::assertSame('blog/third', $this->redirect(1, 'blog/first')?->getTargetPath());
        self::assertSame('blog/third', $this->redirect(1, 'blog/second')?->getTargetPath());
    }

    #[Test]
    public function renaming_back_serves_the_old_url_again_instead_of_redirecting_it(): void
    {
        $this->savePost(7, 'old');
        $this->savePost(7, 'new');
        $this->savePost(7, 'old');

        self::assertNull($this->redirect(1, 'blog/old'));
        self::assertSame('blog/old', $this->redirect(1, 'blog/new')?->getTargetPath());
    }

    #[Test]
    public function another_post_can_take_a_url_key_that_only_a_redirect_holds(): void
    {
        $this->savePost(7, 'old');
        $this->savePost(7, 'new');
        $this->savePost(8, 'old');

        self::assertNull($this->redirect(1, 'blog/old'));
        self::assertSame(['8'], $this->entityIdsServing(1, 'blog/old'));
    }

    #[Test]
    public function a_redirect_keeps_the_type_it_was_given(): void
    {
        $this->savePost(7, 'old');
        $this->savePost(7, 'new');
        foreach ($this->storage->rows as $row) {
            if ($row->getRequestPath() === 'blog/old') {
                $row->setRedirectType(302);
            }
        }
        $this->savePost(7, 'newer');

        self::assertSame(302, (int) $this->redirect(1, 'blog/old')?->getRedirectType());
    }

    private function savePost(int $id, string $urlKey): void
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getPostId')->willReturn($id);
        $post->method('getUrlKey')->willReturn($urlKey);
        $post->method('getStoreIds')->willReturn([1]);

        $factory = new UrlRewriteFactory();
        (new PostPlugin(
            new UrlRewriteBuilder($factory, $this->storeManager(), new Permalink()),
            $this->storage,
            new StaleStoreRewriteCleaner($this->storage, $this->storage),
            new RedirectBuilder($this->storage, $factory, $this->storage),
        ))->afterSave($this->createStub(PostRepositoryInterface::class), $post);
    }

    private function redirect(int $storeId, string $requestPath): ?UrlRewrite
    {
        foreach ($this->storage->rows as $row) {
            if ((int) $row->getStoreId() === $storeId
                && $row->getRequestPath() === $requestPath
                && (int) $row->getRedirectType() > 0
            ) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function entityIdsServing(int $storeId, string $requestPath): array
    {
        $ids = [];
        foreach ($this->storage->rows as $row) {
            if ((int) $row->getStoreId() === $storeId
                && $row->getRequestPath() === $requestPath
                && (int) $row->getRedirectType() === 0
            ) {
                $ids[] = (string) $row->getEntityId();
            }
        }

        return $ids;
    }

    private function storeManager(): StoreManagerInterface
    {
        $stores = array_map(function (int $id): StoreInterface {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);

            return $store;
        }, [1, 2]);

        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStores')->willReturn($stores);

        return $manager;
    }
}

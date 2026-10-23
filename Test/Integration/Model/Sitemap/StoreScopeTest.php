<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\Sitemap;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Sitemap\ItemProvider\Post as PostItemProvider;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * A store's sitemap must not advertise entities that store cannot render.
 * Store assignment used to be checked in PHP against getStoreIds(), which is
 * empty for entities loaded through getList(), so nothing was ever skipped.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class StoreScopeTest extends TestCase
{
    use SecondStoreTrait;

    public function test_post_sitemap_lists_only_posts_visible_on_the_store(): void
    {
        $second = $this->secondStoreId();
        $this->createPost('sm-here', [1]);
        $this->createPost('sm-all', [0]);
        $this->createPost('sm-unassigned', []);
        $this->createPost('sm-elsewhere', [$second]);

        $urls = $this->urlsFor(1);

        self::assertContains('blog/sm-here', $urls);
        self::assertContains('blog/sm-all', $urls);
        self::assertContains('blog/sm-unassigned', $urls);
        self::assertNotContains('blog/sm-elsewhere', $urls);

        self::assertContains('blog/sm-elsewhere', $this->urlsFor($second));
        self::assertNotContains('blog/sm-here', $this->urlsFor($second));
    }

    /**
     * @return string[]
     */
    private function urlsFor(int $storeId): array
    {
        $provider = Bootstrap::getObjectManager()->create(PostItemProvider::class);

        return array_map(
            static fn ($item): string => $item->getUrl(),
            $provider->getItems($storeId)
        );
    }

    /**
     * @param int[] $storeIds
     */
    private function createPost(string $urlKey, array $storeIds): void
    {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)
            ->setUrlKey($urlKey)
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds($storeIds);
        Bootstrap::getObjectManager()->get(PostRepositoryInterface::class)->save($post);
    }
}

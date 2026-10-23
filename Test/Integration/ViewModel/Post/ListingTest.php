<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\ViewModel\Post;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use MageOS\Blog\ViewModel\Post\Listing;
use PHPUnit\Framework\TestCase;

/**
 * The blog index must list the current store's posts, newest first (#27, #35),
 * and its pagination must be computed over that same filtered set.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class ListingTest extends TestCase
{
    use SecondStoreTrait;

    public function test_lists_newest_first(): void
    {
        $this->createPost('oldest', '2023-01-01 10:00:00', [0]);
        $this->createPost('newest', '2025-01-01 10:00:00', [0]);
        $this->createPost('middle', '2024-01-01 10:00:00', [0]);

        self::assertSame(['newest', 'middle', 'oldest'], $this->urlKeys($this->listing()->getItems()));
    }

    public function test_excludes_posts_assigned_to_another_store(): void
    {
        $this->createPost('here', '2024-01-01 10:00:00', [1]);
        $this->createPost('elsewhere', '2025-01-01 10:00:00', [$this->secondStoreId()]);

        self::assertSame(['here'], $this->urlKeys($this->listing()->getItems()));
        self::assertSame(1, $this->listing()->getTotalCount());
    }

    public function test_paginates_over_the_filtered_set_in_date_order(): void
    {
        $this->setPostsPerPage(2);
        $this->createPost('p1', '2025-04-01 10:00:00', [1]);
        $this->createPost('p2', '2025-03-01 10:00:00', [0]);
        $this->createPost('p3', '2025-02-01 10:00:00', [1, $this->secondStoreId()]);
        $this->createPost('hidden', '2025-05-01 10:00:00', [$this->secondStoreId()]);

        $first = $this->listing();
        self::assertSame(['p1', 'p2'], $this->urlKeys($first->getItems()));
        self::assertSame(3, $first->getTotalCount());
        self::assertSame(2, $first->getTotalPages());

        $this->request()->setParam('p', 2);
        self::assertSame(['p3'], $this->urlKeys($this->listing()->getItems()));
    }

    /**
     * @param int[] $storeIds
     */
    private function createPost(string $urlKey, string $publishDate, array $storeIds): void
    {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)
            ->setUrlKey($urlKey)
            ->setStatus(BlogPostStatus::Published->value)
            ->setPublishDate($publishDate)
            ->setStoreIds($storeIds);
        Bootstrap::getObjectManager()->get(PostRepositoryInterface::class)->save($post);
    }

    /**
     * A fresh instance each call: Listing memoises its results per instance.
     */
    private function listing(): Listing
    {
        return Bootstrap::getObjectManager()->create(Listing::class);
    }

    private function request(): RequestInterface
    {
        return Bootstrap::getObjectManager()->get(RequestInterface::class);
    }

    private function setPostsPerPage(int $size): void
    {
        Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class)
            ->setValue(Config::XML_PATH_POSTS_PER_PAGE, (string) $size, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param PostInterface[] $posts
     *
     * @return string[]
     */
    private function urlKeys(array $posts): array
    {
        return array_map(static fn (PostInterface $p): string => (string) $p->getUrlKey(), array_values($posts));
    }
}

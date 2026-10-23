<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * A `store_ids IN (<store>, 0)` criteria is how storefront listings scope posts.
 * The rule it must implement: an entity is visible on a store when it is assigned
 * to that store, assigned to All Store Views (0), or assigned to nothing at all.
 *
 * Regression for the 1.4.2 join: store ids were compared as a GROUP_CONCAT
 * string, which MySQL casts to its leading number, so a post assigned to stores
 * 1 and 2 was invisible on store 2.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class StoreScopeListTest extends TestCase
{
    use SecondStoreTrait;

    public function test_post_assigned_to_several_stores_is_visible_on_each(): void
    {
        $second = $this->secondStoreId();
        $multi = $this->createPost('multi-store', [1, $second]);

        self::assertContains((int) $multi->getPostId(), $this->visibleOn(1));
        self::assertContains((int) $multi->getPostId(), $this->visibleOn($second));
    }

    public function test_post_assigned_to_another_store_is_hidden(): void
    {
        $second = $this->secondStoreId();
        $other = $this->createPost('other-store-only', [$second]);

        self::assertNotContains((int) $other->getPostId(), $this->visibleOn(1));
        self::assertContains((int) $other->getPostId(), $this->visibleOn($second));
    }

    public function test_all_store_views_post_is_visible_everywhere(): void
    {
        $all = $this->createPost('all-stores', [0]);

        self::assertContains((int) $all->getPostId(), $this->visibleOn(1));
        self::assertContains((int) $all->getPostId(), $this->visibleOn($this->secondStoreId()));
    }

    public function test_unassigned_post_is_visible_everywhere(): void
    {
        $unassigned = $this->createPost('unassigned', []);

        self::assertContains((int) $unassigned->getPostId(), $this->visibleOn(1));
        self::assertContains((int) $unassigned->getPostId(), $this->visibleOn($this->secondStoreId()));
    }

    public function test_total_count_matches_the_filtered_set(): void
    {
        $second = $this->secondStoreId();
        $this->createPost('count-a', [1, $second]);
        $this->createPost('count-b', [0]);
        $this->createPost('count-c', [$second]);

        $criteria = $this->criteriaFor(1)->setPageSize(1)->create();
        $results = $this->repository()->getList($criteria);

        self::assertSame(2, $results->getTotalCount(), 'Total must count posts, not joined rows, and honour the filter.');
        self::assertCount(1, $results->getItems());
    }

    /**
     * @return int[]
     */
    private function visibleOn(int $storeId): array
    {
        $items = $this->repository()->getList($this->criteriaFor($storeId)->create())->getItems();

        return array_map(static fn (PostInterface $p): int => (int) $p->getPostId(), array_values($items));
    }

    private function criteriaFor(int $storeId): SearchCriteriaBuilder
    {
        return Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class)
            ->addFilter(PostInterface::STATUS, BlogPostStatus::Published->value)
            ->addFilter(PostInterface::STORE_IDS, [$storeId, 0], 'in');
    }

    /**
     * @param int[] $storeIds
     */
    private function createPost(string $urlKey, array $storeIds): PostInterface
    {
        $post = Bootstrap::getObjectManager()->create(PostInterfaceFactory::class)->create();
        $post->setTitle(ucfirst($urlKey))
            ->setUrlKey($urlKey)
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds($storeIds);

        return $this->repository()->save($post);
    }

    private function repository(): PostRepositoryInterface
    {
        return Bootstrap::getObjectManager()->get(PostRepositoryInterface::class);
    }
}

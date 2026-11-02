<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Breadcrumb;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Breadcrumb\PostCategoryPicker;
use MageOS\Blog\Model\StoreVisibility;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PostCategoryPickerTest extends TestCase
{
    #[Test]
    public function picks_nothing_when_there_are_no_categories(): void
    {
        self::assertNull($this->picker()->pick([], 1));
    }

    #[Test]
    public function picks_the_lowest_position_among_several(): void
    {
        $picked = $this->picker()->pick([
            $this->category(1, 30),
            $this->category(2, 10),
            $this->category(3, 20),
        ], 1);

        self::assertSame(2, $picked?->getCategoryId());
    }

    #[Test]
    public function breaks_position_ties_by_lowest_id(): void
    {
        $picked = $this->picker()->pick([$this->category(9, 5), $this->category(4, 5)], 1);

        self::assertSame(4, $picked?->getCategoryId());
    }

    #[Test]
    public function skips_inactive_categories(): void
    {
        $picked = $this->picker()->pick([
            $this->category(1, 1, active: false),
            $this->category(2, 50),
        ], 1);

        self::assertSame(2, $picked?->getCategoryId());
    }

    #[Test]
    public function picks_nothing_when_every_category_is_inactive(): void
    {
        self::assertNull($this->picker()->pick([$this->category(1, 1, active: false)], 1));
    }

    #[Test]
    public function skips_categories_hidden_on_this_store_view(): void
    {
        $picked = $this->picker()->pick([
            $this->category(1, 1, storeIds: [2]),
            $this->category(2, 50, storeIds: [1]),
        ], 1);

        self::assertSame(2, $picked?->getCategoryId());
    }

    #[Test]
    public function treats_all_store_views_and_unassigned_categories_as_visible(): void
    {
        self::assertSame(1, $this->picker()->pick([$this->category(1, 1, storeIds: [0])], 7)?->getCategoryId());
        self::assertSame(1, $this->picker()->pick([$this->category(1, 1, storeIds: [])], 7)?->getCategoryId());
    }

    #[Test]
    public function for_post_without_category_ids_does_not_query(): void
    {
        $repository = $this->createMock(CategoryRepositoryInterface::class);
        $repository->expects(self::never())->method('getList');
        $post = $this->createStub(PostInterface::class);
        $post->method('getCategoryIds')->willReturn([]);

        $picker = new PostCategoryPicker($repository, $this->createMock(SearchCriteriaBuilder::class), new StoreVisibility());

        self::assertNull($picker->forPost($post, 1));
    }

    #[Test]
    public function for_post_loads_the_assigned_categories_and_picks_one(): void
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getCategoryIds')->willReturn([3, 4]);

        $builder = $this->createMock(SearchCriteriaBuilder::class);
        $builder->expects(self::once())->method('addFilter')->with('category_id', [3, 4], 'in')->willReturnSelf();
        $builder->method('create')->willReturn($this->createStub(SearchCriteriaInterface::class));

        $results = $this->createStub(CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn([$this->category(4, 2), $this->category(3, 1, active: false)]);
        $repository = $this->createStub(CategoryRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);
        $repository->method('getById')->willReturnCallback(
            fn (int $id): CategoryInterface => $this->category($id, $id, active: $id === 4)
        );

        $picker = new PostCategoryPicker($repository, $builder, new StoreVisibility());

        self::assertSame(4, $picker->forPost($post, 1)?->getCategoryId());
    }

    #[Test]
    public function for_post_checks_store_visibility_on_hydrated_categories_not_on_list_items(): void
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getCategoryIds')->willReturn([1, 2]);

        $builder = $this->createStub(SearchCriteriaBuilder::class);
        $builder->method('addFilter')->willReturnSelf();
        $builder->method('create')->willReturn($this->createStub(SearchCriteriaInterface::class));

        // getList() does not hydrate store links, so its items report no store ids at all.
        $results = $this->createStub(CategorySearchResultsInterface::class);
        $results->method('getItems')->willReturn([
            $this->category(1, 0, storeIds: []),
            $this->category(2, 5, storeIds: []),
        ]);
        $repository = $this->createStub(CategoryRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);
        $repository->method('getById')->willReturnCallback(
            fn (int $id): CategoryInterface => $this->category($id, $id === 1 ? 0 : 5, storeIds: $id === 1 ? [2] : [0])
        );

        $picker = new PostCategoryPicker($repository, $builder, new StoreVisibility());

        self::assertSame(2, $picker->forPost($post, 1)?->getCategoryId());
    }

    private function picker(): PostCategoryPicker
    {
        return new PostCategoryPicker(
            $this->createStub(CategoryRepositoryInterface::class),
            $this->createStub(SearchCriteriaBuilder::class),
            new StoreVisibility()
        );
    }

    /**
     * @param int[] $storeIds
     */
    private function category(int $id, int $position, bool $active = true, array $storeIds = [1]): CategoryInterface
    {
        $category = $this->createStub(CategoryInterface::class);
        $category->method('getCategoryId')->willReturn($id);
        $category->method('getPosition')->willReturn($position);
        $category->method('getIsActive')->willReturn($active);
        $category->method('getStoreIds')->willReturn($storeIds);

        return $category;
    }
}

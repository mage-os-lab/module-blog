<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Hydrator;

use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\Category\Link\StoreLinkManager as CategoryStoreLinkManager;
use MageOS\Blog\Model\Post\Link\CategoryLinkManager;
use MageOS\Blog\Model\Post\Link\StoreLinkManager as PostStoreLinkManager;
use MageOS\Blog\Model\Post\Link\TagLinkManager;
use MageOS\Blog\Model\Resolver\Hydrator\LinkHydrator;
use MageOS\Blog\Model\Tag\Link\StoreLinkManager as TagStoreLinkManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LinkHydratorTest extends TestCase
{
    #[Test]
    public function posts_get_their_links_with_one_batched_call_per_link_table(): void
    {
        $stores = $this->createMock(PostStoreLinkManager::class);
        $stores->expects(self::once())->method('getLinkedIdsForMany')->with([1, 2])->willReturn([1 => [0], 2 => [1, 2]]);
        $stores->expects(self::never())->method('getLinkedIds');
        $categories = $this->createMock(CategoryLinkManager::class);
        $categories->expects(self::once())->method('getLinkedIdsForMany')->with([1, 2])->willReturn([1 => [7], 2 => []]);
        $tags = $this->createMock(TagLinkManager::class);
        $tags->expects(self::once())->method('getLinkedIdsForMany')->with([1, 2])->willReturn([1 => [], 2 => [5, 6]]);

        $first = $this->createMock(PostInterface::class);
        $first->method('getPostId')->willReturn(1);
        $first->expects(self::once())->method('setStoreIds')->with([0]);
        $first->expects(self::once())->method('setCategoryIds')->with([7]);
        $first->expects(self::once())->method('setTagIds')->with([]);
        $second = $this->createMock(PostInterface::class);
        $second->method('getPostId')->willReturn(2);
        $second->expects(self::once())->method('setStoreIds')->with([1, 2]);
        $second->expects(self::once())->method('setCategoryIds')->with([]);
        $second->expects(self::once())->method('setTagIds')->with([5, 6]);

        $this->hydrator(postStores: $stores, postCategories: $categories, postTags: $tags)->hydratePosts([$first, $second]);
    }

    #[Test]
    public function an_empty_page_runs_no_query(): void
    {
        $stores = $this->createMock(PostStoreLinkManager::class);
        $stores->expects(self::never())->method('getLinkedIdsForMany');
        $categories = $this->createMock(CategoryStoreLinkManager::class);
        $categories->expects(self::never())->method('getLinkedIdsForMany');
        $tags = $this->createMock(TagStoreLinkManager::class);
        $tags->expects(self::never())->method('getLinkedIdsForMany');

        $hydrator = $this->hydrator(postStores: $stores, categoryStores: $categories, tagStores: $tags);
        $hydrator->hydratePosts([]);
        $hydrator->hydrateCategories([]);
        $hydrator->hydrateTags([]);
    }

    #[Test]
    public function categories_get_their_store_ids(): void
    {
        $stores = $this->createMock(CategoryStoreLinkManager::class);
        $stores->expects(self::once())->method('getLinkedIdsForMany')->with([4, 5])->willReturn([4 => [1], 5 => []]);
        $first = $this->createMock(CategoryInterface::class);
        $first->method('getCategoryId')->willReturn(4);
        $first->expects(self::once())->method('setStoreIds')->with([1]);
        $second = $this->createMock(CategoryInterface::class);
        $second->method('getCategoryId')->willReturn(5);
        $second->expects(self::once())->method('setStoreIds')->with([]);

        $this->hydrator(categoryStores: $stores)->hydrateCategories([$first, $second]);
    }

    #[Test]
    public function tags_get_their_store_ids(): void
    {
        $stores = $this->createMock(TagStoreLinkManager::class);
        $stores->expects(self::once())->method('getLinkedIdsForMany')->with([8])->willReturn([8 => [0]]);
        $tag = $this->createMock(TagInterface::class);
        $tag->method('getTagId')->willReturn(8);
        $tag->expects(self::once())->method('setStoreIds')->with([0]);

        $this->hydrator(tagStores: $stores)->hydrateTags([$tag]);
    }

    private function hydrator(
        ?PostStoreLinkManager $postStores = null,
        ?CategoryLinkManager $postCategories = null,
        ?TagLinkManager $postTags = null,
        ?CategoryStoreLinkManager $categoryStores = null,
        ?TagStoreLinkManager $tagStores = null,
    ): LinkHydrator {
        return new LinkHydrator(
            $postStores ?? $this->createMock(PostStoreLinkManager::class),
            $postCategories ?? $this->createMock(CategoryLinkManager::class),
            $postTags ?? $this->createMock(TagLinkManager::class),
            $categoryStores ?? $this->createMock(CategoryStoreLinkManager::class),
            $tagStores ?? $this->createMock(TagStoreLinkManager::class),
        );
    }
}

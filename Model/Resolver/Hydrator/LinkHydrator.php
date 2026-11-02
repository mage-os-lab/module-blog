<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Hydrator;

use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\Category\Link\StoreLinkManager as CategoryStoreLinkManager;
use MageOS\Blog\Model\Post\Link\CategoryLinkManager;
use MageOS\Blog\Model\Post\Link\StoreLinkManager as PostStoreLinkManager;
use MageOS\Blog\Model\Post\Link\TagLinkManager;
use MageOS\Blog\Model\Tag\Link\StoreLinkManager as TagStoreLinkManager;

/**
 * Fills the link ids (stores, categories, tags) of a page of list items the way getById() does for
 * one entity, with one query per link table for the whole page.
 *
 * The repositories' getList() deliberately leaves the links out: the storefront, the sitemap and
 * the admin pickers never read them. Only the GraphQL list resolvers do, so only they pay for it,
 * and they call this after the page was already filtered by ReadPolicy.
 */
class LinkHydrator
{
    public function __construct(
        private readonly PostStoreLinkManager $postStores,
        private readonly CategoryLinkManager $postCategories,
        private readonly TagLinkManager $postTags,
        private readonly CategoryStoreLinkManager $categoryStores,
        private readonly TagStoreLinkManager $tagStores,
    ) {
    }

    /**
     * @param PostInterface[] $posts
     */
    public function hydratePosts(array $posts): void
    {
        $ids = array_map(static fn (PostInterface $post): int => (int) $post->getPostId(), $posts);
        if ($ids === []) {
            return;
        }

        $stores = $this->postStores->getLinkedIdsForMany($ids);
        $categories = $this->postCategories->getLinkedIdsForMany($ids);
        $tags = $this->postTags->getLinkedIdsForMany($ids);
        foreach ($posts as $post) {
            $id = (int) $post->getPostId();
            $post->setStoreIds($stores[$id] ?? []);
            $post->setCategoryIds($categories[$id] ?? []);
            $post->setTagIds($tags[$id] ?? []);
        }
    }

    /**
     * @param CategoryInterface[] $categories
     */
    public function hydrateCategories(array $categories): void
    {
        $ids = array_map(static fn (CategoryInterface $category): int => (int) $category->getCategoryId(), $categories);
        if ($ids === []) {
            return;
        }

        $stores = $this->categoryStores->getLinkedIdsForMany($ids);
        foreach ($categories as $category) {
            $category->setStoreIds($stores[(int) $category->getCategoryId()] ?? []);
        }
    }

    /**
     * @param TagInterface[] $tags
     */
    public function hydrateTags(array $tags): void
    {
        $ids = array_map(static fn (TagInterface $tag): int => (int) $tag->getTagId(), $tags);
        if ($ids === []) {
            return;
        }

        $stores = $this->tagStores->getLinkedIdsForMany($ids);
        foreach ($tags as $tag) {
            $tag->setStoreIds($stores[(int) $tag->getTagId()] ?? []);
        }
    }
}

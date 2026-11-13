<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Url;

use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;

class UrlRewriteBuilder
{
    public const ENTITY_TYPE_POST = 'blog_post';
    public const ENTITY_TYPE_CATEGORY = 'blog_category';
    public const ENTITY_TYPE_TAG = 'blog_tag';
    public const ENTITY_TYPE_AUTHOR = 'blog_author';
    public const ENTITY_TYPE_INDEX = 'blog_index';
    public const ENTITY_TYPE_SEARCH = 'blog_search';

    /**
     * Full routes of the pages the front name also serves bare. A rewrite target must never be a
     * path another rewrite claims, or a redirect from the bare front name would loop.
     */
    public const TARGET_PATH_INDEX = 'blog/index/index';
    public const TARGET_PATH_SEARCH = 'blog/search/index';

    public function __construct(
        private readonly UrlRewriteFactory $urlRewriteFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Permalink $permalink,
    ) {
    }

    /**
     * @param int[] $storeIds
     * @return UrlRewrite[]
     */
    public function buildForPost(PostInterface $post, array $storeIds): array
    {
        $postId = (int) $post->getPostId();
        $key = $post->getUrlKey();
        $requestPath = fn (int $storeId): string => $this->permalink->getPostPath($key, $storeId);
        $targetPath = 'blog/post/view/id/' . $postId;

        return $this->buildRows(
            self::ENTITY_TYPE_POST,
            $postId,
            $requestPath,
            $targetPath,
            $this->expandStoreIds($storeIds),
        );
    }

    /**
     * @param int[] $storeIds
     * @return UrlRewrite[]
     */
    public function buildForCategory(CategoryInterface $category, array $storeIds): array
    {
        $categoryId = (int) $category->getCategoryId();
        $key = $category->getUrlKey();
        $requestPath = fn (int $storeId): string => $this->permalink->getCategoryPath($key, $storeId);
        $targetPath = 'blog/category/view/id/' . $categoryId;

        return $this->buildRows(
            self::ENTITY_TYPE_CATEGORY,
            $categoryId,
            $requestPath,
            $targetPath,
            $this->expandStoreIds($storeIds),
        );
    }

    /**
     * @param int[] $storeIds
     * @return UrlRewrite[]
     */
    public function buildForTag(TagInterface $tag, array $storeIds): array
    {
        $tagId = (int) $tag->getTagId();
        $key = $tag->getUrlKey();
        $requestPath = fn (int $storeId): string => $this->permalink->getTagPath($key, $storeId);
        $targetPath = 'blog/tag/view/id/' . $tagId;

        return $this->buildRows(
            self::ENTITY_TYPE_TAG,
            $tagId,
            $requestPath,
            $targetPath,
            $this->expandStoreIds($storeIds),
        );
    }

    /**
     * @return UrlRewrite[]
     */
    public function buildForAuthor(AuthorInterface $author): array
    {
        $authorId = (int) $author->getAuthorId();
        $key = $author->getSlug();
        $requestPath = fn (int $storeId): string => $this->permalink->getAuthorPath($key, $storeId);
        $targetPath = 'blog/author/view/id/' . $authorId;

        return $this->buildRows(
            self::ENTITY_TYPE_AUTHOR,
            $authorId,
            $requestPath,
            $targetPath,
            $this->allStoreIds(),
        );
    }

    /**
     * Rewrites of the blog home page, one per store view.
     *
     * Every store gets one, also when it keeps the default path: the store switcher finds the
     * page on the other store by target path, so a store without a row would send visitors to
     * the homepage.
     *
     * @return UrlRewrite[]
     */
    public function buildForIndex(): array
    {
        return $this->buildRows(
            self::ENTITY_TYPE_INDEX,
            0,
            fn (int $storeId): string => $this->permalink->getIndexPath($storeId),
            self::TARGET_PATH_INDEX,
            $this->allStoreIds(),
        );
    }

    /**
     * Rewrites of the blog search page, one per store view, for the same reason as buildForIndex().
     *
     * @return UrlRewrite[]
     */
    public function buildForSearch(): array
    {
        return $this->buildRows(
            self::ENTITY_TYPE_SEARCH,
            0,
            fn (int $storeId): string => $this->permalink->getSearchPath($storeId),
            self::TARGET_PATH_SEARCH,
            $this->allStoreIds(),
        );
    }

    /**
     * @param \Closure(int): string $requestPath request path for a store id; segments are store-scoped
     * @param int[] $storeIds
     * @return UrlRewrite[]
     */
    private function buildRows(
        string $entityType,
        int $entityId,
        \Closure $requestPath,
        string $targetPath,
        array $storeIds,
    ): array {
        $rows = [];
        foreach ($storeIds as $storeId) {
            $rewrite = $this->urlRewriteFactory->create();
            $rewrite->setEntityType($entityType)
                ->setEntityId($entityId)
                ->setRequestPath($requestPath($storeId))
                ->setTargetPath($targetPath)
                ->setIsAutogenerated(1)
                ->setStoreId($storeId)
                ->setRedirectType(0);
            $rows[] = $rewrite;
        }

        return $rows;
    }

    /**
     * An unassigned entity is treated like store 0: rewrites for every store, never a silent no-op.
     *
     * @param int[] $storeIds
     * @return int[]
     */
    private function expandStoreIds(array $storeIds): array
    {
        $normalized = array_values(array_unique(array_map('intval', $storeIds)));
        if ($normalized === [] || \in_array(0, $normalized, true)) {
            return $this->allStoreIds();
        }

        return $normalized;
    }

    /**
     * Store 0 is the pivot marker for "all store views", never a rewrite target of its own.
     *
     * @return int[]
     */
    private function allStoreIds(): array
    {
        $ids = [];
        foreach ($this->storeManager->getStores() as $store) {
            $ids[] = (int) $store->getId();
        }

        return $ids;
    }
}

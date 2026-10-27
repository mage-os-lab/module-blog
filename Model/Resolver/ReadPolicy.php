<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\StoreVisibility;

/**
 * What a GraphQL caller may read.
 *
 * A visitor (no token, a customer token, or an admin token without the ACL resource) sees
 * what the storefront shows: published posts, active categories, tags and authors, and for
 * posts, categories and tags only those visible on the current store view. An admin token
 * whose role grants the entity's ACL resource sees everything, drafts included.
 *
 * Every blog read resolver goes through here. Keep the rules in step with the storefront
 * controllers (Controller/{Post,Category,Tag,Author}/View).
 *
 * Not covered: core's `urlResolver` / `route` queries, which answer from url_rewrite rows
 * that exist for every entity whatever its status. The blog's enabled flag is not consulted either.
 */
class ReadPolicy
{
    private const ACL_POST = 'MageOS_Blog::post';
    private const ACL_CATEGORY = 'MageOS_Blog::category';
    private const ACL_TAG = 'MageOS_Blog::tag';
    private const ACL_AUTHOR = 'MageOS_Blog::author';

    public function __construct(
        private readonly AdminAuthorization $adminAuthorization,
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreVisibility $storeVisibility,
    ) {
    }

    /**
     * Repository filters a post list must always apply (see FilterBuilder::build()).
     *
     * @return array<string, int>
     */
    public function postListFilters(ContextInterface $context): array
    {
        if ($this->adminAuthorization->isAuthorized($context, self::ACL_POST)) {
            return [];
        }

        return [
            PostInterface::STATUS => BlogPostStatus::Published->value,
            PostInterface::STORE_IDS => $this->currentStoreId(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function categoryListFilters(ContextInterface $context): array
    {
        if ($this->adminAuthorization->isAuthorized($context, self::ACL_CATEGORY)) {
            return [];
        }

        return [
            CategoryInterface::IS_ACTIVE => 1,
            CategoryInterface::STORE_IDS => $this->currentStoreId(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function tagListFilters(ContextInterface $context): array
    {
        if ($this->adminAuthorization->isAuthorized($context, self::ACL_TAG)) {
            return [];
        }

        return [
            TagInterface::IS_ACTIVE => 1,
            TagInterface::STORE_IDS => $this->currentStoreId(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function authorListFilters(ContextInterface $context): array
    {
        if ($this->adminAuthorization->isAuthorized($context, self::ACL_AUTHOR)) {
            return [];
        }

        return [AuthorInterface::IS_ACTIVE => 1];
    }

    public function canReadPost(ContextInterface $context, PostInterface $post): bool
    {
        $isPublic = $post->getStatus() === BlogPostStatus::Published->value
            && $this->isOnCurrentStore($post->getStoreIds());

        return $isPublic || $this->adminAuthorization->isAuthorized($context, self::ACL_POST);
    }

    public function canReadCategory(ContextInterface $context, CategoryInterface $category): bool
    {
        return ($category->getIsActive() && $this->isOnCurrentStore($category->getStoreIds()))
            || $this->adminAuthorization->isAuthorized($context, self::ACL_CATEGORY);
    }

    public function canReadTag(ContextInterface $context, TagInterface $tag): bool
    {
        return ($tag->getIsActive() && $this->isOnCurrentStore($tag->getStoreIds()))
            || $this->adminAuthorization->isAuthorized($context, self::ACL_TAG);
    }

    public function canReadAuthor(ContextInterface $context, AuthorInterface $author): bool
    {
        return $author->getIsActive()
            || $this->adminAuthorization->isAuthorized($context, self::ACL_AUTHOR);
    }

    /**
     * @param int[] $storeIds
     */
    private function isOnCurrentStore(array $storeIds): bool
    {
        return $this->storeVisibility->isVisible($storeIds, $this->currentStoreId());
    }

    private function currentStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}

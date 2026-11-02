<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Breadcrumb;

use Magento\Framework\Api\SearchCriteriaBuilder;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\StoreVisibility;

/**
 * Chooses the category a post breadcrumb trail runs through.
 *
 * A post can sit in several categories, so the choice is a convention: the
 * first one that is active and visible on the current store view, ordered by
 * position and then by id so the result is stable. Visibility uses the same
 * rule as the storefront (StoreVisibility), so a trail never links to a
 * category page that would answer 404.
 */
class PostCategoryPicker
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StoreVisibility $storeVisibility,
    ) {
    }

    public function forPost(PostInterface $post, int $storeId): ?CategoryInterface
    {
        $categoryIds = $post->getCategoryIds();
        if ($categoryIds === []) {
            return null;
        }

        $criteria = $this->searchCriteriaBuilder
            ->addFilter('category_id', $categoryIds, 'in')
            ->create();

        return $this->pick($this->categoryRepository->getList($criteria)->getItems(), $storeId);
    }

    /**
     * @param CategoryInterface[] $categories
     */
    public function pick(array $categories, int $storeId): ?CategoryInterface
    {
        $candidates = array_values(array_filter(
            $categories,
            fn (CategoryInterface $category): bool => $category->getIsActive()
                && $this->storeVisibility->isVisible($category->getStoreIds(), $storeId)
        ));
        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (CategoryInterface $a, CategoryInterface $b): int
                => [$a->getPosition(), (int) $a->getCategoryId()] <=> [$b->getPosition(), (int) $b->getCategoryId()]
        );

        return $candidates[0];
    }
}

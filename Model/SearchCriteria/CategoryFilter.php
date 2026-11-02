<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection\AbstractDb;
use MageOS\Blog\Model\ResourceModel\Post\Collection;

/**
 * Handles a `category_ids` search-criteria filter on posts: the link lives in the
 * `mageos_blog_post_category` pivot, there is no such column on the post table.
 */
class CategoryFilter extends AbstractIdListFilter
{
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        if (!$collection instanceof Collection) {
            return false;
        }

        $collection->addCategoryFilter($this->idsOf($filter));

        return true;
    }
}

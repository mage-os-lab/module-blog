<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection\AbstractDb;
use MageOS\Blog\Model\ResourceModel\Post\Collection;

/**
 * Handles a `tag_ids` search-criteria filter on posts: the link lives in the
 * `mageos_blog_post_tag` pivot, there is no such column on the post table.
 */
class TagFilter extends AbstractIdListFilter
{
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        if (!$collection instanceof Collection) {
            return false;
        }

        $collection->addTagFilter($this->idsOf($filter));

        return true;
    }
}

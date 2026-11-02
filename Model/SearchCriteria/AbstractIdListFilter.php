<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;

/**
 * Base for post filters on an id list that lives in a pivot table (category_ids, tag_ids).
 */
abstract class AbstractIdListFilter implements CustomFilterInterface
{
    /**
     * @return int[]
     */
    protected function idsOf(Filter $filter): array
    {
        $value = $filter->getValue();

        return array_map('intval', \is_array($value) ? $value : explode(',', (string) $value));
    }
}

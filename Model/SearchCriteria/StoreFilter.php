<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use MageOS\Blog\Model\ResourceModel\AbstractStoreScopedCollection;

/**
 * Handles a `store_ids` search-criteria filter, which is a pivot-table concept
 * rather than a column on the entity table.
 */
class StoreFilter implements CustomFilterInterface
{
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        if (!$collection instanceof AbstractStoreScopedCollection) {
            return false;
        }

        $value = $filter->getValue();
        $collection->addStoreFilter(
            array_map('intval', \is_array($value) ? $value : explode(',', (string) $value))
        );

        return true;
    }
}

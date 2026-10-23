<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

/**
 * The single rule for whether a post, category or tag is visible on a store view.
 *
 * Visible when assigned to that store, assigned to All Store Views (0), or
 * assigned to no store at all. The unassigned case matches how
 * UrlRewriteBuilder expands an entity across stores, so a page that has rewrites
 * is also a page that renders. The SQL equivalent is
 * AbstractStoreScopedCollection::addStoreFilter(); keep the two in step.
 */
class StoreVisibility
{
    /**
     * @param int[] $entityStoreIds
     */
    public function isVisible(array $entityStoreIds, int $storeId): bool
    {
        return $entityStoreIds === []
            || \in_array(0, $entityStoreIds, true)
            || \in_array($storeId, $entityStoreIds, true);
    }
}

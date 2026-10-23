<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Base for collections whose entities are assigned to store views through a
 * `<entity>_store` pivot table.
 *
 * Store scoping is applied as a filter on the pivot, never as a join onto the
 * collection: a join exposes the pivot's id column next to the entity's own, so
 * any unqualified filter or sort on the id fails with SQLSTATE 1052 (ambiguous
 * column), and a one-to-many join fans rows out and corrupts getSize().
 */
abstract class AbstractStoreScopedCollection extends AbstractCollection
{
    /**
     * @return string Unprefixed name of the `<entity>_store` pivot table.
     */
    abstract protected function getStorePivotTable(): string;

    /**
     * Restrict the collection to entities visible on the given store view(s).
     *
     * An entity is visible when it is assigned to one of the stores, assigned to
     * All Store Views (0), or assigned to no store at all. The last case follows
     * the rule UrlRewriteBuilder and the sitemap providers already apply: an
     * unassigned entity is treated like store 0, not as hidden.
     *
     * @param int|int[] $storeIds
     *
     * @return $this
     */
    public function addStoreFilter(int|array $storeIds): static
    {
        $ids = array_values(array_unique([...array_map('intval', (array) $storeIds), 0]));

        $linked = $this->getConnection()->select()
            ->from(['store_link' => $this->getTable($this->getStorePivotTable())], [new \Zend_Db_Expr('1')])
            ->where('store_link.' . $this->getIdFieldName() . ' = main_table.' . $this->getIdFieldName());
        $linkedToStore = (clone $linked)->where('store_link.store_id IN (?)', $ids);

        $this->getSelect()->where(\sprintf('EXISTS (%s) OR NOT EXISTS (%s)', $linkedToStore, $linked));

        return $this;
    }
}

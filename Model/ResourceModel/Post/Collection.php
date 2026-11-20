<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Post;

use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\ResourceModel\AbstractStoreScopedCollection;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;

class Collection extends AbstractStoreScopedCollection
{
    protected $_idFieldName = 'post_id';

    protected function _construct(): void
    {
        $this->_init(Post::class, PostResource::class);
    }

    protected function getStorePivotTable(): string
    {
        return 'mageos_blog_post_store';
    }

    /**
     * Restrict the collection to posts assigned to at least one of the categories.
     *
     * @param int[] $categoryIds
     *
     * @return $this
     */
    public function addCategoryFilter(array $categoryIds): static
    {
        return $this->addPivotFilter('mageos_blog_post_category', 'category_id', $categoryIds);
    }

    /**
     * Restrict the collection to posts assigned to at least one of the tags.
     *
     * @param int[] $tagIds
     *
     * @return $this
     */
    public function addTagFilter(array $tagIds): static
    {
        return $this->addPivotFilter('mageos_blog_post_tag', 'tag_id', $tagIds);
    }

    /**
     * An EXISTS on the pivot, never a join: a join would fan one post out into a row per
     * match and corrupt getSize() (see AbstractStoreScopedCollection).
     *
     * @param int[] $ids
     *
     * @return $this
     */
    private function addPivotFilter(string $pivotTable, string $column, array $ids): static
    {
        $linked = $this->getConnection()->select()
            ->from(['relation' => $this->getTable($pivotTable)], [new \Zend_Db_Expr('1')])
            ->where('relation.post_id = main_table.post_id')
            ->where('relation.' . $column . ' IN (?)', array_values(array_unique($ids)));

        $this->getSelect()->where(\sprintf('EXISTS (%s)', $linked));

        return $this;
    }
}

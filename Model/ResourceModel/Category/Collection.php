<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Category;

use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\ResourceModel\AbstractStoreScopedCollection;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractStoreScopedCollection
{
    protected $_idFieldName = 'category_id';

    protected function _construct(): void
    {
        $this->_init(Category::class, CategoryResource::class);
    }

    protected function getStorePivotTable(): string
    {
        return 'mageos_blog_category_store';
    }
}

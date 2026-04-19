<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'category_id';

    protected function _construct(): void
    {
        $this->_init(Category::class, CategoryResource::class);
    }
}

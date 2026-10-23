<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Tag;

use MageOS\Blog\Model\ResourceModel\AbstractStoreScopedCollection;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;
use MageOS\Blog\Model\Tag;

class Collection extends AbstractStoreScopedCollection
{
    protected $_idFieldName = 'tag_id';

    protected function _construct(): void
    {
        $this->_init(Tag::class, TagResource::class);
    }

    protected function getStorePivotTable(): string
    {
        return 'mageos_blog_tag_store';
    }
}

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
}

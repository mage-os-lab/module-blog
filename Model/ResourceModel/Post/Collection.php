<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Post;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'post_id';

    protected function _construct(): void
    {
        $this->_init(Post::class, PostResource::class);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Tag;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;
use MageOS\Blog\Model\Tag;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'tag_id';

    protected function _construct(): void
    {
        $this->_init(Tag::class, TagResource::class);
    }
}

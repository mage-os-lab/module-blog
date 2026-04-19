<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel\Tag;

use MageOS\Blog\Model\Tag;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'tag_id';

    protected function _construct(): void
    {
        $this->_init(Tag::class, TagResource::class);
    }
}

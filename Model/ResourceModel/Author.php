<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Author extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('mageos_blog_author', 'author_id');
    }
}

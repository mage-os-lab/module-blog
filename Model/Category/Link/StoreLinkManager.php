<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Category\Link;

use MageOS\Blog\Model\Link\AbstractPivotLinkManager;

final class StoreLinkManager extends AbstractPivotLinkManager
{
    protected function pivotTable(): string
    {
        return 'mageos_blog_category_store';
    }

    protected function leftColumn(): string
    {
        return 'category_id';
    }

    protected function rightColumn(): string
    {
        return 'store_id';
    }
}

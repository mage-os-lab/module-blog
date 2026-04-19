<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Post\Link;

final class StoreLinkManager extends AbstractPivotLinkManager
{
    protected function pivotTable(): string
    {
        return 'mageos_blog_post_store';
    }

    protected function leftColumn(): string
    {
        return 'post_id';
    }

    protected function rightColumn(): string
    {
        return 'store_id';
    }
}

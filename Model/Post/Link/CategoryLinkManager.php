<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Post\Link;

final class CategoryLinkManager extends AbstractPivotLinkManager
{
    protected function pivotTable(): string
    {
        return 'mageos_blog_post_category';
    }

    protected function leftColumn(): string
    {
        return 'post_id';
    }

    protected function rightColumn(): string
    {
        return 'category_id';
    }
}

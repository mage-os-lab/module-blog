<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Post\Link;

use MageOS\Blog\Model\Link\AbstractPivotLinkManager;

final class TagLinkManager extends AbstractPivotLinkManager
{
    protected function pivotTable(): string
    {
        return 'mageos_blog_post_tag';
    }

    protected function leftColumn(): string
    {
        return 'post_id';
    }

    protected function rightColumn(): string
    {
        return 'tag_id';
    }
}

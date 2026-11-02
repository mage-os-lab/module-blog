<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Resolver\Post\ParentPostsResolver;

class PostsResolver extends ParentPostsResolver
{
    protected function postField(): string
    {
        return PostInterface::CATEGORY_IDS;
    }
}

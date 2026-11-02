<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Tag;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Resolver\Post\ParentPostsResolver;

class PostsResolver extends ParentPostsResolver
{
    protected function postField(): string
    {
        return PostInterface::TAG_IDS;
    }
}

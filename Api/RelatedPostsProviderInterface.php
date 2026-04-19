<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

use MageOS\Blog\Api\Data\PostInterface;

interface RelatedPostsProviderInterface
{
    /** @return PostInterface[] */
    public function forPost(PostInterface $post, int $limit = 5): array;
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

interface PostManagementInterface
{
    public function publish(int $postId): void;
    public function incrementViews(int $postId): void;
    public function computeReadingTime(string $content): int;
}

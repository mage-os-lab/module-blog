<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\UrlKeyGenerator;

interface CollisionChecker
{
    public function isTaken(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): bool;
}

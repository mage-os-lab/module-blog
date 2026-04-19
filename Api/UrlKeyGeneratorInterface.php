<?php

declare(strict_types=1);

namespace MageOS\Blog\Api;

interface UrlKeyGeneratorInterface
{
    public const ENTITY_POST = 'post';
    public const ENTITY_CATEGORY = 'category';
    public const ENTITY_TAG = 'tag';
    public const ENTITY_AUTHOR = 'author';

    /**
     * Reserved path segments that cannot be used as a url_key.
     * @return string[]
     */
    public const RESERVED = ['category', 'tag', 'author', 'search', 'rss', 'page', 'feed'];

    /**
     * @throws \InvalidArgumentException when title produces a reserved slug and cannot be suffixed.
     */
    public function generate(string $title, string $entityType, ?int $storeId = null): string;

    /**
     * @throws \InvalidArgumentException when the slug is reserved or already in use.
     */
    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void;
}

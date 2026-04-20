<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Mapper;

use MageOS\Blog\Api\Data\TagInterface;

class TagMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(TagInterface $tag): array
    {
        return [
            'id' => $tag->getTagId(),
            'url_key' => $tag->getUrlKey(),
            'title' => $tag->getTitle(),
            'description' => $tag->getDescription(),
            'meta_title' => $tag->getMetaTitle(),
            'meta_description' => $tag->getMetaDescription(),
            'is_active' => $tag->getIsActive(),
            'store_ids' => $tag->getStoreIds(),
        ];
    }
}

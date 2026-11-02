<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Mapper;

use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Resolver\UidCodec;

class CategoryMapper
{
    public function __construct(private readonly UidCodec $uidCodec)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(CategoryInterface $cat): array
    {
        return [
            'id' => $cat->getCategoryId(),
            'uid' => $this->uidCodec->encode((int) $cat->getCategoryId()),
            'url_key' => $cat->getUrlKey(),
            'title' => $cat->getTitle(),
            'description' => $cat->getDescription(),
            'parent_id' => $cat->getParentId(),
            'position' => $cat->getPosition(),
            'meta_title' => $cat->getMetaTitle(),
            'meta_description' => $cat->getMetaDescription(),
            'meta_keywords' => $cat->getMetaKeywords(),
            'include_in_menu' => $cat->getIncludeInMenu(),
            'include_in_sidebar' => $cat->getIncludeInSidebar(),
            'is_active' => $cat->getIsActive(),
            'store_ids' => $cat->getStoreIds(),
        ];
    }
}

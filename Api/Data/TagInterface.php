<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface TagInterface extends ExtensibleDataInterface
{
    public const TAG_ID = 'tag_id';
    public const URL_KEY = 'url_key';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const IS_ACTIVE = 'is_active';
    public const STORE_IDS = 'store_ids';

    public function getTagId(): ?int;
    public function setTagId(int $id): self;
    public function getUrlKey(): string;
    public function setUrlKey(string $urlKey): self;
    public function getTitle(): string;
    public function setTitle(string $title): self;
    public function getDescription(): ?string;
    public function setDescription(?string $description): self;
    public function getMetaTitle(): ?string;
    public function setMetaTitle(?string $title): self;
    public function getMetaDescription(): ?string;
    public function setMetaDescription(?string $desc): self;
    public function getIsActive(): bool;
    public function setIsActive(bool $flag): self;
    /** @return int[] */
    public function getStoreIds(): array;
    /** @param int[] $storeIds */
    public function setStoreIds(array $storeIds): self;

    public function getExtensionAttributes(): ?TagExtensionInterface;
    public function setExtensionAttributes(TagExtensionInterface $extensionAttributes): self;
}

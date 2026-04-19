<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface CategoryInterface extends ExtensibleDataInterface
{
    public const CATEGORY_ID = 'category_id';
    public const URL_KEY = 'url_key';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const PARENT_ID = 'parent_id';
    public const POSITION = 'position';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const META_KEYWORDS = 'meta_keywords';
    public const INCLUDE_IN_MENU = 'include_in_menu';
    public const INCLUDE_IN_SIDEBAR = 'include_in_sidebar';
    public const IS_ACTIVE = 'is_active';
    public const STORE_IDS = 'store_ids';

    public function getCategoryId(): ?int;
    public function setCategoryId(int $id): self;
    public function getUrlKey(): string;
    public function setUrlKey(string $urlKey): self;
    public function getTitle(): string;
    public function setTitle(string $title): self;
    public function getDescription(): ?string;
    public function setDescription(?string $description): self;
    public function getParentId(): ?int;
    public function setParentId(?int $id): self;
    public function getPosition(): int;
    public function setPosition(int $position): self;
    public function getMetaTitle(): ?string;
    public function setMetaTitle(?string $title): self;
    public function getMetaDescription(): ?string;
    public function setMetaDescription(?string $desc): self;
    public function getMetaKeywords(): ?string;
    public function setMetaKeywords(?string $keywords): self;
    public function getIncludeInMenu(): bool;
    public function setIncludeInMenu(bool $flag): self;
    public function getIncludeInSidebar(): bool;
    public function setIncludeInSidebar(bool $flag): self;
    public function getIsActive(): bool;
    public function setIsActive(bool $flag): self;
    /** @return int[] */
    public function getStoreIds(): array;
    /** @param int[] $storeIds */
    public function setStoreIds(array $storeIds): self;

    public function getExtensionAttributes(): ?CategoryExtensionInterface;
    public function setExtensionAttributes(CategoryExtensionInterface $extensionAttributes): self;
}

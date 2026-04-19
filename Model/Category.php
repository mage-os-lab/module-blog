<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use MageOS\Blog\Api\Data\CategoryExtensionInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;

class Category extends AbstractExtensibleModel implements CategoryInterface, IdentityInterface
{
    public const CACHE_TAG = 'mageos_blog_category';

    protected $_eventPrefix = 'mageos_blog_category';
    protected $_eventObject = 'category';

    protected function _construct(): void
    {
        $this->_init(CategoryResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getCategoryId(): ?int
    {
        $value = $this->getData(self::CATEGORY_ID);
        return $value === null ? null : (int) $value;
    }

    public function setCategoryId(int $id): self
    {
        return $this->setData(self::CATEGORY_ID, $id);
    }

    public function getUrlKey(): string
    {
        return (string) $this->getData(self::URL_KEY);
    }

    public function setUrlKey(string $urlKey): self
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    public function setTitle(string $title): self
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getDescription(): ?string
    {
        $value = $this->getData(self::DESCRIPTION);
        return $value === null ? null : (string) $value;
    }

    public function setDescription(?string $description): self
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getParentId(): ?int
    {
        $value = $this->getData(self::PARENT_ID);
        return $value === null ? null : (int) $value;
    }

    public function setParentId(?int $id): self
    {
        return $this->setData(self::PARENT_ID, $id);
    }

    public function getPosition(): int
    {
        return (int) $this->getData(self::POSITION);
    }

    public function setPosition(int $position): self
    {
        return $this->setData(self::POSITION, $position);
    }

    public function getMetaTitle(): ?string
    {
        $value = $this->getData(self::META_TITLE);
        return $value === null ? null : (string) $value;
    }

    public function setMetaTitle(?string $title): self
    {
        return $this->setData(self::META_TITLE, $title);
    }

    public function getMetaDescription(): ?string
    {
        $value = $this->getData(self::META_DESCRIPTION);
        return $value === null ? null : (string) $value;
    }

    public function setMetaDescription(?string $desc): self
    {
        return $this->setData(self::META_DESCRIPTION, $desc);
    }

    public function getMetaKeywords(): ?string
    {
        $value = $this->getData(self::META_KEYWORDS);
        return $value === null ? null : (string) $value;
    }

    public function setMetaKeywords(?string $keywords): self
    {
        return $this->setData(self::META_KEYWORDS, $keywords);
    }

    public function getIncludeInMenu(): bool
    {
        return (bool) $this->getData(self::INCLUDE_IN_MENU);
    }

    public function setIncludeInMenu(bool $flag): self
    {
        return $this->setData(self::INCLUDE_IN_MENU, $flag);
    }

    public function getIncludeInSidebar(): bool
    {
        return (bool) $this->getData(self::INCLUDE_IN_SIDEBAR);
    }

    public function setIncludeInSidebar(bool $flag): self
    {
        return $this->setData(self::INCLUDE_IN_SIDEBAR, $flag);
    }

    public function getIsActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $flag): self
    {
        return $this->setData(self::IS_ACTIVE, $flag);
    }

    public function getStoreIds(): array
    {
        $ids = $this->getData(self::STORE_IDS);
        return \is_array($ids) ? array_map('intval', $ids) : [];
    }

    public function setStoreIds(array $storeIds): self
    {
        return $this->setData(self::STORE_IDS, array_map('intval', $storeIds));
    }

    public function getExtensionAttributes(): ?CategoryExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    public function setExtensionAttributes(CategoryExtensionInterface $extensionAttributes): self
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

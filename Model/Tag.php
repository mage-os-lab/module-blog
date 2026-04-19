<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use MageOS\Blog\Api\Data\TagExtensionInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;

class Tag extends AbstractExtensibleModel implements TagInterface, IdentityInterface
{
    public const CACHE_TAG = 'mageos_blog_tag';

    protected $_eventPrefix = 'mageos_blog_tag';
    protected $_eventObject = 'tag';

    protected function _construct(): void
    {
        $this->_init(TagResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getTagId(): ?int
    {
        $value = $this->getData(self::TAG_ID);
        return $value === null ? null : (int) $value;
    }

    public function setTagId(int $id): self
    {
        return $this->setData(self::TAG_ID, $id);
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

    public function getExtensionAttributes(): ?TagExtensionInterface
    {
        /** @var ?TagExtensionInterface $value */
        $value = $this->_getExtensionAttributes();

        return $value;
    }

    public function setExtensionAttributes(TagExtensionInterface $extensionAttributes): self
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

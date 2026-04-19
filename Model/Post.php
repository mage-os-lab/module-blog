<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use MageOS\Blog\Api\Data\PostExtensionInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;

class Post extends AbstractExtensibleModel implements PostInterface, IdentityInterface
{
    public const CACHE_TAG = 'mageos_blog_post';

    protected $_eventPrefix = 'mageos_blog_post';
    protected $_eventObject = 'post';

    protected function _construct(): void
    {
        $this->_init(PostResource::class);
    }

    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getPostId(): ?int
    {
        $value = $this->getData(self::POST_ID);
        return $value === null ? null : (int) $value;
    }

    public function setPostId(int $id): self
    {
        return $this->setData(self::POST_ID, $id);
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

    public function getContent(): ?string
    {
        $value = $this->getData(self::CONTENT);
        return $value === null ? null : (string) $value;
    }

    public function setContent(?string $content): self
    {
        return $this->setData(self::CONTENT, $content);
    }

    public function getShortContent(): ?string
    {
        $value = $this->getData(self::SHORT_CONTENT);
        return $value === null ? null : (string) $value;
    }

    public function setShortContent(?string $content): self
    {
        return $this->setData(self::SHORT_CONTENT, $content);
    }

    public function getFeaturedImage(): ?string
    {
        $value = $this->getData(self::FEATURED_IMAGE);
        return $value === null ? null : (string) $value;
    }

    public function setFeaturedImage(?string $path): self
    {
        return $this->setData(self::FEATURED_IMAGE, $path);
    }

    public function getFeaturedImageAlt(): ?string
    {
        $value = $this->getData(self::FEATURED_IMAGE_ALT);
        return $value === null ? null : (string) $value;
    }

    public function setFeaturedImageAlt(?string $alt): self
    {
        return $this->setData(self::FEATURED_IMAGE_ALT, $alt);
    }

    public function getAuthorId(): ?int
    {
        $value = $this->getData(self::AUTHOR_ID);
        return $value === null ? null : (int) $value;
    }

    public function setAuthorId(?int $id): self
    {
        return $this->setData(self::AUTHOR_ID, $id);
    }

    public function getPublishDate(): ?string
    {
        $value = $this->getData(self::PUBLISH_DATE);
        return $value === null ? null : (string) $value;
    }

    public function setPublishDate(?string $date): self
    {
        return $this->setData(self::PUBLISH_DATE, $date);
    }

    public function getReadingTime(): ?int
    {
        $value = $this->getData(self::READING_TIME);
        return $value === null ? null : (int) $value;
    }

    public function setReadingTime(?int $minutes): self
    {
        return $this->setData(self::READING_TIME, $minutes);
    }

    public function getViewsCount(): int
    {
        return (int) $this->getData(self::VIEWS_COUNT);
    }

    public function setViewsCount(int $count): self
    {
        return $this->setData(self::VIEWS_COUNT, $count);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): self
    {
        return $this->setData(self::STATUS, $status);
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

    public function getMetaRobots(): ?string
    {
        $value = $this->getData(self::META_ROBOTS);
        return $value === null ? null : (string) $value;
    }

    public function setMetaRobots(?string $robots): self
    {
        return $this->setData(self::META_ROBOTS, $robots);
    }

    public function getOgTitle(): ?string
    {
        $value = $this->getData(self::OG_TITLE);
        return $value === null ? null : (string) $value;
    }

    public function setOgTitle(?string $title): self
    {
        return $this->setData(self::OG_TITLE, $title);
    }

    public function getOgDescription(): ?string
    {
        $value = $this->getData(self::OG_DESCRIPTION);
        return $value === null ? null : (string) $value;
    }

    public function setOgDescription(?string $desc): self
    {
        return $this->setData(self::OG_DESCRIPTION, $desc);
    }

    public function getOgImage(): ?string
    {
        $value = $this->getData(self::OG_IMAGE);
        return $value === null ? null : (string) $value;
    }

    public function setOgImage(?string $path): self
    {
        return $this->setData(self::OG_IMAGE, $path);
    }

    public function getOgType(): ?string
    {
        $value = $this->getData(self::OG_TYPE);
        return $value === null ? null : (string) $value;
    }

    public function setOgType(?string $type): self
    {
        return $this->setData(self::OG_TYPE, $type);
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

    public function getCategoryIds(): array
    {
        $ids = $this->getData(self::CATEGORY_IDS);
        return \is_array($ids) ? array_map('intval', $ids) : [];
    }

    public function setCategoryIds(array $ids): self
    {
        return $this->setData(self::CATEGORY_IDS, array_map('intval', $ids));
    }

    public function getTagIds(): array
    {
        $ids = $this->getData(self::TAG_IDS);
        return \is_array($ids) ? array_map('intval', $ids) : [];
    }

    public function setTagIds(array $ids): self
    {
        return $this->setData(self::TAG_IDS, array_map('intval', $ids));
    }

    public function getExtensionAttributes(): ?PostExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    public function setExtensionAttributes(PostExtensionInterface $extensionAttributes): self
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}

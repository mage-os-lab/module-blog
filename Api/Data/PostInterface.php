<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PostInterface extends ExtensibleDataInterface
{
    public const POST_ID = 'post_id';
    public const URL_KEY = 'url_key';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const SHORT_CONTENT = 'short_content';
    public const FEATURED_IMAGE = 'featured_image';
    public const FEATURED_IMAGE_ALT = 'featured_image_alt';
    public const AUTHOR_ID = 'author_id';
    public const PUBLISH_DATE = 'publish_date';
    public const READING_TIME = 'reading_time';
    public const VIEWS_COUNT = 'views_count';
    public const STATUS = 'status';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const META_KEYWORDS = 'meta_keywords';
    public const META_ROBOTS = 'meta_robots';
    public const OG_TITLE = 'og_title';
    public const OG_DESCRIPTION = 'og_description';
    public const OG_IMAGE = 'og_image';
    public const OG_TYPE = 'og_type';
    public const STORE_IDS = 'store_ids';
    public const CATEGORY_IDS = 'category_ids';
    public const TAG_IDS = 'tag_ids';

    public function getPostId(): ?int;
    public function setPostId(int $id): self;
    public function getUrlKey(): string;
    public function setUrlKey(string $urlKey): self;
    public function getTitle(): string;
    public function setTitle(string $title): self;
    public function getContent(): ?string;
    public function setContent(?string $content): self;
    public function getShortContent(): ?string;
    public function setShortContent(?string $content): self;
    public function getFeaturedImage(): ?string;
    public function setFeaturedImage(?string $path): self;
    public function getFeaturedImageAlt(): ?string;
    public function setFeaturedImageAlt(?string $alt): self;
    public function getAuthorId(): ?int;
    public function setAuthorId(?int $id): self;
    public function getPublishDate(): ?string;
    public function setPublishDate(?string $date): self;
    public function getReadingTime(): ?int;
    public function setReadingTime(?int $minutes): self;
    public function getViewsCount(): int;
    public function setViewsCount(int $count): self;
    public function getStatus(): int;
    public function setStatus(int $status): self;
    public function getMetaTitle(): ?string;
    public function setMetaTitle(?string $title): self;
    public function getMetaDescription(): ?string;
    public function setMetaDescription(?string $desc): self;
    public function getMetaKeywords(): ?string;
    public function setMetaKeywords(?string $keywords): self;
    public function getMetaRobots(): ?string;
    public function setMetaRobots(?string $robots): self;
    public function getOgTitle(): ?string;
    public function setOgTitle(?string $title): self;
    public function getOgDescription(): ?string;
    public function setOgDescription(?string $desc): self;
    public function getOgImage(): ?string;
    public function setOgImage(?string $path): self;
    public function getOgType(): ?string;
    public function setOgType(?string $type): self;
    /** @return int[] */
    public function getStoreIds(): array;
    /** @param int[] $storeIds */
    public function setStoreIds(array $storeIds): self;
    /** @return int[] */
    public function getCategoryIds(): array;
    /** @param int[] $ids */
    public function setCategoryIds(array $ids): self;
    /** @return int[] */
    public function getTagIds(): array;
    /** @param int[] $ids */
    public function setTagIds(array $ids): self;

    public function getExtensionAttributes(): ?PostExtensionInterface;
    public function setExtensionAttributes(PostExtensionInterface $extensionAttributes): self;
}

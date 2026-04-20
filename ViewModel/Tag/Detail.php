<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Tag;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Controller\Tag\View as TagViewController;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;

class Detail implements ArgumentInterface
{
    /**
     * @var PostInterface[]|null
     */
    private ?array $cachedPosts = null;

    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder,
        private readonly PostsByAssignmentProvider $postsProvider,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function getTag(): ?TagInterface
    {
        $tag = $this->registry->registry(TagViewController::REGISTRY_KEY);

        return $tag instanceof TagInterface ? $tag : null;
    }

    public function getTitle(): string
    {
        $tag = $this->getTag();

        return $tag !== null ? (string) $tag->getTitle() : '';
    }

    public function getDescription(): ?string
    {
        return $this->getTag()?->getDescription();
    }

    public function getCanonicalUrl(): string
    {
        $tag = $this->getTag();

        return $tag === null
            ? ''
            : $this->urlBuilder->getUrl('blog/tag/' . $tag->getUrlKey());
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        if ($this->cachedPosts !== null) {
            return $this->cachedPosts;
        }
        $tag = $this->getTag();
        if ($tag === null) {
            return $this->cachedPosts = [];
        }

        $tagId = $tag->getTagId();
        if ($tagId === null) {
            return $this->cachedPosts = [];
        }

        return $this->cachedPosts = $this->postsProvider->byTag(
            $tagId,
            (int) $this->storeManager->getStore()->getId(),
        );
    }

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }

    public function getFormattedPublishDate(PostInterface $post): string
    {
        $date = $post->getPublishDate();
        if ($date === null || $date === '') {
            return '';
        }
        try {
            return (new \DateTimeImmutable($date))->format('F j, Y');
        } catch (\Throwable) {
            return '';
        }
    }
}

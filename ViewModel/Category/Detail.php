<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Category;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Category\View as CategoryViewController;
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

    public function getCategory(): ?CategoryInterface
    {
        $category = $this->registry->registry(CategoryViewController::REGISTRY_KEY);

        return $category instanceof CategoryInterface ? $category : null;
    }

    public function getTitle(): string
    {
        $category = $this->getCategory();

        return $category !== null ? (string) $category->getTitle() : '';
    }

    public function getDescription(): ?string
    {
        return $this->getCategory()?->getDescription();
    }

    public function getCanonicalUrl(): string
    {
        $category = $this->getCategory();

        return $category === null
            ? ''
            : $this->urlBuilder->getUrl('blog/category/' . $category->getUrlKey());
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        if ($this->cachedPosts !== null) {
            return $this->cachedPosts;
        }
        $category = $this->getCategory();
        if ($category === null) {
            return $this->cachedPosts = [];
        }

        $categoryId = $category->getCategoryId();
        if ($categoryId === null) {
            return $this->cachedPosts = [];
        }

        return $this->cachedPosts = $this->postsProvider->byCategory(
            $categoryId,
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

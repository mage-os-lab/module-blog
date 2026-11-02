<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Category;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Category\View as CategoryViewController;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\ViewModel\Post\PostPresenterInterface;
use MageOS\Blog\ViewModel\Post\PostPresenterTrait;

class Detail implements ArgumentInterface, PostPresenterInterface
{
    use PostPresenterTrait;

    /**
     * @var PostInterface[]|null
     */
    private ?array $cachedPosts = null;

    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder,
        private readonly PostsByAssignmentProvider $postsProvider,
        private readonly StoreManagerInterface $storeManager,
        private readonly AuthorRepositoryInterface $authorRepository,
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
            : $this->urlBuilder->getUrl(
                '',
                ['_direct' => 'blog/category/' . $category->getUrlKey()]
            );
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

    private function urlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    private function authorRepository(): AuthorRepositoryInterface
    {
        return $this->authorRepository;
    }
}

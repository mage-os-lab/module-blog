<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Category;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Controller\Category\View as CategoryViewController;

class Detail implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder
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
}

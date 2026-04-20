<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Tag;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Controller\Tag\View as TagViewController;

class Detail implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder
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
}

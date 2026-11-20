<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Tag;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Controller\Tag\View as TagViewController;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Post\PostsByAssignmentProvider;
use MageOS\Blog\Model\Url\Permalink;
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
        private readonly Config $config,
        private readonly Permalink $permalink,
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
            : $this->urlBuilder->getUrl(
                '',
                ['_direct' => $this->permalink->getTagPath($tag->getUrlKey())]
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

    private function urlBuilder(): UrlInterface
    {
        return $this->urlBuilder;
    }

    private function authorRepository(): AuthorRepositoryInterface
    {
        return $this->authorRepository;
    }

    private function config(): Config
    {
        return $this->config;
    }

    private function permalink(): Permalink
    {
        return $this->permalink;
    }
}

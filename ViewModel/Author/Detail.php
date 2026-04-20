<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Author;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Author\View as AuthorViewController;
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

    public function getAuthor(): ?AuthorInterface
    {
        $author = $this->registry->registry(AuthorViewController::REGISTRY_KEY);

        return $author instanceof AuthorInterface ? $author : null;
    }

    public function getTitle(): string
    {
        $author = $this->getAuthor();

        return $author !== null ? (string) $author->getName() : '';
    }

    public function getBio(): ?string
    {
        return $this->getAuthor()?->getBio();
    }

    public function getAvatarUrl(): ?string
    {
        $author = $this->getAuthor();
        if ($author === null || $author->getAvatar() === null || $author->getAvatar() === '') {
            return null;
        }

        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
            . 'mageos_blog/author/' . $author->getAvatar();
    }

    public function getEmail(): ?string
    {
        return $this->getAuthor()?->getEmail();
    }

    public function getTwitter(): ?string
    {
        return $this->getAuthor()?->getTwitter();
    }

    public function getLinkedin(): ?string
    {
        return $this->getAuthor()?->getLinkedin();
    }

    public function getWebsite(): ?string
    {
        return $this->getAuthor()?->getWebsite();
    }

    public function getCanonicalUrl(): string
    {
        $author = $this->getAuthor();

        return $author === null
            ? ''
            : $this->urlBuilder->getUrl('blog/author/' . $author->getSlug());
    }

    /**
     * @return PostInterface[]
     */
    public function getPosts(): array
    {
        if ($this->cachedPosts !== null) {
            return $this->cachedPosts;
        }
        $author = $this->getAuthor();
        if ($author === null) {
            return $this->cachedPosts = [];
        }

        $authorId = $author->getAuthorId();
        if ($authorId === null) {
            return $this->cachedPosts = [];
        }

        return $this->cachedPosts = $this->postsProvider->byAuthor(
            $authorId,
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

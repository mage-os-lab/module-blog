<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Author;

use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Controller\Author\View as AuthorViewController;

class Detail implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder
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
}

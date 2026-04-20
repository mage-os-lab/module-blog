<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Controller\Post\View as PostViewController;
use MageOS\Blog\Model\Config;

class Detail implements ArgumentInterface
{
    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function getPost(): ?PostInterface
    {
        $post = $this->registry->registry(PostViewController::REGISTRY_KEY);

        return $post instanceof PostInterface ? $post : null;
    }

    public function getTitle(): string
    {
        $post = $this->getPost();

        return $post !== null ? (string) $post->getTitle() : '';
    }

    public function getContent(): ?string
    {
        return $this->getPost()?->getContent();
    }

    public function getShortContent(): ?string
    {
        return $this->getPost()?->getShortContent();
    }

    public function getFeaturedImageUrl(): ?string
    {
        $post = $this->getPost();
        if ($post === null || $post->getFeaturedImage() === null || $post->getFeaturedImage() === '') {
            return null;
        }

        return $this->mediaUrl() . 'mageos_blog/' . $post->getFeaturedImage();
    }

    public function getFeaturedImageAlt(): string
    {
        $post = $this->getPost();

        return $post === null ? '' : (string) ($post->getFeaturedImageAlt() ?? $post->getTitle());
    }

    public function getFormattedPublishDate(): string
    {
        $post = $this->getPost();
        if ($post === null) {
            return '';
        }
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

    public function getCanonicalUrl(): string
    {
        $post = $this->getPost();

        return $post === null
            ? ''
            : (string) $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }

    /**
     * OpenGraph meta tag map (<meta property="X" content="Y" />).
     *
     * @return array<string, string>
     */
    public function getOgTags(): array
    {
        $post = $this->getPost();
        if ($post === null) {
            return [];
        }

        $type = (string) ($post->getOgType() !== null && $post->getOgType() !== ''
            ? $post->getOgType()
            : ($this->config->getOgDefaultType() !== '' ? $this->config->getOgDefaultType() : 'article'));

        $tags = [
            'og:type' => $type,
            'og:title' => $this->resolveOg($post, 'title'),
            'og:description' => $this->resolveOg($post, 'description'),
            'og:url' => $this->getCanonicalUrl(),
        ];

        $ogImage = $this->getOgImageUrl();
        if ($ogImage !== null) {
            $tags['og:image'] = $ogImage;
        }

        $published = $this->formatIso($post->getPublishDate());
        if ($published !== '') {
            $tags['article:published_time'] = $published;
        }
        $updated = $this->formatIso($this->readTimestamp($post, 'update_time'));
        if ($updated !== '') {
            $tags['article:modified_time'] = $updated;
        }

        return array_filter($tags, static fn (string $v): bool => $v !== '');
    }

    /**
     * Twitter card meta tag map.
     *
     * @return array<string, string>
     */
    public function getTwitterTags(): array
    {
        $post = $this->getPost();
        if ($post === null) {
            return [];
        }

        $ogImage = $this->getOgImageUrl();
        $card = $ogImage !== null ? 'summary_large_image' : 'summary';

        $tags = [
            'twitter:card' => $card,
            'twitter:title' => $this->resolveOg($post, 'title'),
            'twitter:description' => $this->resolveOg($post, 'description'),
        ];

        if ($ogImage !== null) {
            $tags['twitter:image'] = $ogImage;
        }

        $site = $this->config->getTwitterSite();
        if ($site !== '') {
            $tags['twitter:site'] = $site;
        }

        return array_filter($tags, static fn (string $v): bool => $v !== '');
    }

    public function getJsonLd(): string
    {
        if (!$this->config->isJsonLdEnabled()) {
            return '';
        }
        $post = $this->getPost();
        if ($post === null) {
            return '';
        }

        $store = $this->storeManager->getStore();
        $publisher = [
            '@type' => 'Organization',
            'name' => (string) $store->getName(),
            'url' => $store->getBaseUrl(),
        ];

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $this->resolveOg($post, 'title'),
            'publisher' => $publisher,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $this->getCanonicalUrl(),
            ],
        ];

        $published = $this->formatIso($post->getPublishDate());
        if ($published !== '') {
            $data['datePublished'] = $published;
        }
        $updated = $this->formatIso($this->readTimestamp($post, 'update_time'));
        if ($updated !== '') {
            $data['dateModified'] = $updated;
        }

        $img = $this->getOgImageUrl() ?? $this->getFeaturedImageUrl();
        if ($img !== null) {
            $data['image'] = $img;
        }

        $description = $this->resolveOg($post, 'description');
        if ($description !== '') {
            $data['description'] = $description;
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json !== false ? $json : '';
    }

    private function getOgImageUrl(): ?string
    {
        $post = $this->getPost();
        if ($post === null) {
            return null;
        }
        if ($post->getOgImage() !== null && $post->getOgImage() !== '') {
            return $this->mediaUrl() . 'mageos_blog/' . $post->getOgImage();
        }

        return $this->getFeaturedImageUrl();
    }

    private function resolveOg(PostInterface $post, string $field): string
    {
        $ogValue = $this->readField($post, 'og_' . $field);
        if ($ogValue !== '') {
            return $ogValue;
        }
        $metaValue = $this->readField($post, 'meta_' . $field);
        if ($metaValue !== '') {
            return $metaValue;
        }

        return $field === 'title'
            ? (string) $post->getTitle()
            : (string) ($post->getShortContent() ?? '');
    }

    private function readField(PostInterface $post, string $field): string
    {
        $getter = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
        if (!method_exists($post, $getter)) {
            return '';
        }
        $value = $post->{$getter}();

        return $value === null ? '' : (string) $value;
    }

    private function readTimestamp(PostInterface $post, string $key): ?string
    {
        if ($post instanceof DataObject) {
            $value = $post->getData($key);

            return $value === null ? null : (string) $value;
        }

        return null;
    }

    private function mediaUrl(): string
    {
        return $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
    }

    private function formatIso(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($datetime))->format(\DateTimeInterface::ATOM);
        } catch (\Throwable) {
            return '';
        }
    }
}

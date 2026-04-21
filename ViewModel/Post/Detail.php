<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\RelatedPostsProviderInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Controller\Post\View as PostViewController;
use MageOS\Blog\Model\Config;

class Detail implements ArgumentInterface
{
    /**
     * @var array<int, AuthorInterface|false>
     */
    private array $authorCache = [];

    public function __construct(
        private readonly Registry $registry,
        private readonly UrlInterface $urlBuilder,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly RelatedPostsProviderInterface $relatedPostsProvider
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

    public function getFeaturedImageUrl(?PostInterface $post = null): ?string
    {
        $post ??= $this->getPost();
        if ($post === null) {
            return null;
        }
        $path = (string) $post->getFeaturedImage();
        if ($path === '') {
            return null;
        }

        return $this->mediaUrl() . 'mageos_blog/' . $path;
    }

    public function getFeaturedImageAlt(?PostInterface $post = null): string
    {
        $post ??= $this->getPost();

        return $post === null ? '' : (string) ($post->getFeaturedImageAlt() ?? $post->getTitle());
    }

    public function getFormattedPublishDate(?PostInterface $post = null): string
    {
        $post ??= $this->getPost();
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

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder->getUrl('blog/' . $post->getUrlKey());
    }

    public function getAuthorName(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);

        return $author === null ? null : (string) $author->getName();
    }

    public function getAuthorUrl(PostInterface $post): ?string
    {
        $author = $this->loadAuthor($post);
        if ($author === null) {
            return null;
        }
        $slug = (string) $author->getSlug();

        return $slug === '' ? null : $this->urlBuilder->getUrl('blog/author/' . $slug);
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    public function getTags(): array
    {
        $post = $this->getPost();
        if ($post === null) {
            return [];
        }
        $tagIds = $post->getTagIds();
        if ($tagIds === []) {
            return [];
        }
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('tag_id', $tagIds, 'in')
            ->create();
        $tags = $this->tagRepository->getList($criteria)->getItems();
        $out = [];
        /** @var \MageOS\Blog\Api\Data\TagInterface $tag */
        foreach ($tags as $tag) {
            $out[] = [
                'title' => (string) $tag->getTitle(),
                'url' => $this->urlBuilder->getUrl('blog/tag/' . $tag->getUrlKey()),
            ];
        }

        return $out;
    }

    /**
     * @return PostInterface[]
     */
    public function getRelatedPosts(int $limit = 3): array
    {
        $post = $this->getPost();
        if ($post === null) {
            return [];
        }

        return $this->relatedPostsProvider->forPost($post, $limit);
    }

    private function loadAuthor(PostInterface $post): ?AuthorInterface
    {
        $id = $post->getAuthorId();
        if ($id === null || $id <= 0) {
            return null;
        }
        if (\array_key_exists($id, $this->authorCache)) {
            $cached = $this->authorCache[$id];

            return $cached === false ? null : $cached;
        }
        try {
            $author = $this->authorRepository->getById((int) $id);
        } catch (NoSuchEntityException) {
            $this->authorCache[$id] = false;

            return null;
        }
        $this->authorCache[$id] = $author;

        return $author;
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

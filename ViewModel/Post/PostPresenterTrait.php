<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\Permalink;

/**
 * Shared implementation of PostPresenterInterface.
 *
 * Author name and URL return null when the show_author flag is off, which every
 * template already treats as "no byline".
 *
 * The collaborators are supplied by the composing class through the abstract
 * accessors below, so each ViewModel keeps its own constructor shape and its own
 * promoted properties.
 */
trait PostPresenterTrait
{
    /**
     * @var array<int, AuthorInterface|false>
     */
    private array $authorCache = [];

    abstract private function urlBuilder(): UrlInterface;

    abstract private function authorRepository(): AuthorRepositoryInterface;

    abstract private function config(): Config;

    abstract private function permalink(): Permalink;

    public function getPostUrl(PostInterface $post): string
    {
        return $this->urlBuilder()->getUrl(
            '',
            ['_direct' => $this->permalink()->getPostPath($post->getUrlKey())]
        );
    }

    public function getFeaturedImageUrl(PostInterface $post): ?string
    {
        $path = (string) $post->getFeaturedImage();
        if ($path === '') {
            return null;
        }

        return $this->mediaUrl() . 'mageos_blog/' . ltrim($path, '/');
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

    public function getAuthorName(PostInterface $post): ?string
    {
        if (!$this->config()->isShowAuthor()) {
            return null;
        }
        $author = $this->loadAuthor($post);

        return $author === null ? null : (string) $author->getName();
    }

    public function getAuthorUrl(PostInterface $post): ?string
    {
        if (!$this->config()->isShowAuthor()) {
            return null;
        }
        $author = $this->loadAuthor($post);
        if ($author === null) {
            return null;
        }
        $slug = (string) $author->getSlug();

        return $slug === '' ? null : $this->urlBuilder()->getUrl(
            '',
            ['_direct' => $this->permalink()->getAuthorPath($slug)]
        );
    }

    private function mediaUrl(): string
    {
        return rtrim($this->urlBuilder()->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/') . '/';
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
            $author = $this->authorRepository()->getById((int) $id);
        } catch (NoSuchEntityException) {
            $this->authorCache[$id] = false;

            return null;
        }
        $this->authorCache[$id] = $author;

        return $author;
    }
}

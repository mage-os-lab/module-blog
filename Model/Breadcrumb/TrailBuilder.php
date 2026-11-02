<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Breadcrumb;

use Magento\Framework\UrlInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;

/**
 * Maps a blog page to its breadcrumb trail.
 *
 * Returns plain crumb arrays in the shape Magento\Theme\Block\Html\Breadcrumbs::addCrumb()
 * takes, keyed by crumb name, so it needs no layout. The last crumb never has a
 * link: it is the current page.
 *
 * @phpstan-type Crumb array{label: string, title: string, link?: string}
 */
class TrailBuilder
{
    public function __construct(private readonly UrlInterface $urlBuilder)
    {
    }

    /**
     * @return array<string, Crumb>
     */
    public function forIndex(): array
    {
        return $this->root(false);
    }

    /**
     * @return array<string, Crumb>
     */
    public function forPost(PostInterface $post, ?CategoryInterface $category): array
    {
        $trail = $this->root(true);
        if ($category !== null) {
            $trail['blog_category'] = $this->crumb(
                $category->getTitle(),
                $this->url('blog/category/' . $category->getUrlKey())
            );
        }
        $trail['blog_post'] = $this->crumb($post->getTitle());

        return $trail;
    }

    /**
     * @return array<string, Crumb>
     */
    public function forCategory(CategoryInterface $category): array
    {
        return $this->root(true) + ['blog_category' => $this->crumb($category->getTitle())];
    }

    /**
     * @return array<string, Crumb>
     */
    public function forTag(TagInterface $tag): array
    {
        return $this->root(true) + ['blog_tag' => $this->crumb((string) __('Tagged "%1"', $tag->getTitle()))];
    }

    /**
     * @return array<string, Crumb>
     */
    public function forAuthor(AuthorInterface $author): array
    {
        return $this->root(true) + ['blog_author' => $this->crumb($author->getName())];
    }

    /**
     * @return array<string, Crumb>
     */
    public function forSearch(string $query): array
    {
        return $this->root(true) + ['blog_search' => $this->crumb((string) __('Search: %1', $query))];
    }

    /**
     * Home, then Blog. Blog links everywhere except on the index itself.
     *
     * @return array<string, Crumb>
     */
    private function root(bool $linkBlog): array
    {
        return [
            'home' => $this->crumb(
                (string) __('Home'),
                (string) $this->urlBuilder->getUrl(''),
                (string) __('Go to Home Page')
            ),
            'blog' => $this->crumb((string) __('Blog'), $linkBlog ? $this->url('blog') : null),
        ];
    }

    /**
     * @return Crumb
     */
    private function crumb(string $label, ?string $link = null, ?string $title = null): array
    {
        $crumb = ['label' => $label, 'title' => $title ?? $label];
        if ($link !== null) {
            $crumb['link'] = $link;
        }

        return $crumb;
    }

    private function url(string $path): string
    {
        return (string) $this->urlBuilder->getUrl('', ['_direct' => $path]);
    }
}

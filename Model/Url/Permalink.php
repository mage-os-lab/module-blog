<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Url;

/**
 * Request paths of every storefront blog page.
 *
 * The url_rewrite rows, the rendered links, the sitemap, the RSS feed and the breadcrumbs all
 * read their paths from here, so a page cannot be linked under one path and served under another.
 */
class Permalink
{
    /**
     * Front name from etc/frontend/routes.xml. Rewrite target paths always start with it.
     */
    public const ROUTE = 'blog';

    public const SEGMENT_SEARCH = 'search';
    public const SEGMENT_CATEGORY = 'category';
    public const SEGMENT_TAG = 'tag';
    public const SEGMENT_AUTHOR = 'author';

    public function getIndexPath(): string
    {
        return self::ROUTE;
    }

    public function getSearchPath(): string
    {
        return $this->getIndexPath() . '/' . self::SEGMENT_SEARCH;
    }

    public function getPostPath(string $urlKey): string
    {
        return $this->getIndexPath() . '/' . $urlKey;
    }

    public function getCategoryPath(string $urlKey): string
    {
        return $this->getIndexPath() . '/' . self::SEGMENT_CATEGORY . '/' . $urlKey;
    }

    public function getTagPath(string $urlKey): string
    {
        return $this->getIndexPath() . '/' . self::SEGMENT_TAG . '/' . $urlKey;
    }

    public function getAuthorPath(string $slug): string
    {
        return $this->getIndexPath() . '/' . self::SEGMENT_AUTHOR . '/' . $slug;
    }
}

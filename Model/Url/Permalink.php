<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Url;

use MageOS\Blog\Model\Config;

/**
 * Request paths of every storefront blog page, per store view.
 *
 * The url_rewrite rows, the rendered links, the sitemap, the RSS feed and the breadcrumbs all
 * read their paths from here, so a page cannot be linked under one path and served under another.
 *
 * The route and the search, category, tag and author segments come from
 * Stores > Configuration > Blog > Permalinks.
 * A value that is empty or not a valid segment falls back to the default, so a value that got
 * past the backend model (config.php, a direct database edit) cannot produce rewrites the
 * storefront cannot serve.
 */
class Permalink
{
    /**
     * Front name from etc/frontend/routes.xml. Rewrite target paths always start with it, whatever
     * route the store serves the blog under.
     */
    public const ROUTE = 'blog';

    public const SEGMENT_ROUTE = 'route';
    public const SEGMENT_SEARCH = 'search';
    public const SEGMENT_CATEGORY = 'category';
    public const SEGMENT_TAG = 'tag';
    public const SEGMENT_AUTHOR = 'author';

    /**
     * Lowercase letters and digits, optionally joined by single "-" or "_". No "/": one URL segment.
     */
    public const SEGMENT_PATTERN = '/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/';

    /**
     * Configurable segment => config path.
     */
    private const CONFIG_PATHS = [
        self::SEGMENT_ROUTE => Config::XML_PATH_PERMALINK_ROUTE,
        self::SEGMENT_SEARCH => Config::XML_PATH_PERMALINK_SEARCH,
        self::SEGMENT_CATEGORY => Config::XML_PATH_PERMALINK_CATEGORY,
        self::SEGMENT_TAG => Config::XML_PATH_PERMALINK_TAG,
        self::SEGMENT_AUTHOR => Config::XML_PATH_PERMALINK_AUTHOR,
    ];

    /**
     * Segment => value used when nothing valid is configured: the paths the module always had.
     */
    private const DEFAULTS = [
        self::SEGMENT_ROUTE => self::ROUTE,
        self::SEGMENT_SEARCH => 'search',
        self::SEGMENT_CATEGORY => 'category',
        self::SEGMENT_TAG => 'tag',
        self::SEGMENT_AUTHOR => 'author',
    ];

    public function __construct(private readonly Config $config)
    {
    }

    public function getIndexPath(?int $storeId = null): string
    {
        return $this->segment(self::SEGMENT_ROUTE, $storeId);
    }

    public function getSearchPath(?int $storeId = null): string
    {
        return $this->getIndexPath($storeId) . '/' . $this->segment(self::SEGMENT_SEARCH, $storeId);
    }

    public function getPostPath(string $urlKey, ?int $storeId = null): string
    {
        return $this->getIndexPath($storeId) . '/' . $urlKey;
    }

    public function getCategoryPath(string $urlKey, ?int $storeId = null): string
    {
        return $this->prefixed(self::SEGMENT_CATEGORY, $urlKey, $storeId);
    }

    public function getTagPath(string $urlKey, ?int $storeId = null): string
    {
        return $this->prefixed(self::SEGMENT_TAG, $urlKey, $storeId);
    }

    public function getAuthorPath(string $slug, ?int $storeId = null): string
    {
        return $this->prefixed(self::SEGMENT_AUTHOR, $slug, $storeId);
    }

    /**
     * Every segment a post URL key must not take on this store, because it already names a page type.
     *
     * @return string[]
     */
    public function getSegments(?int $storeId = null): array
    {
        $segments = [];
        foreach ([self::SEGMENT_SEARCH, self::SEGMENT_CATEGORY, self::SEGMENT_TAG, self::SEGMENT_AUTHOR] as $segment) {
            $segments[] = $this->segment($segment, $storeId);
        }

        return array_values(array_unique($segments));
    }

    private function prefixed(string $segment, string $key, ?int $storeId): string
    {
        return $this->getIndexPath($storeId) . '/' . $this->segment($segment, $storeId) . '/' . $key;
    }

    private function segment(string $segment, ?int $storeId): string
    {
        $value = strtolower(trim($this->config->getPermalinkSegment(self::CONFIG_PATHS[$segment], $storeId), '/'));

        return preg_match(self::SEGMENT_PATTERN, $value) === 1 ? $value : self::DEFAULTS[$segment];
    }
}

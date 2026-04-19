<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const XML_PATH_ENABLED = 'mageos_blog/general/enabled';

    public const XML_PATH_POSTS_PER_PAGE = 'mageos_blog/post/posts_per_page';
    public const XML_PATH_DEFAULT_ROBOTS = 'mageos_blog/post/default_robots';

    public const XML_PATH_SIDEBAR_SEARCH_ENABLED = 'mageos_blog/sidebar/search_enabled';
    public const XML_PATH_SIDEBAR_SEARCH_SORT_ORDER = 'mageos_blog/sidebar/search_sort_order';
    public const XML_PATH_SIDEBAR_RECENT_POSTS_ENABLED = 'mageos_blog/sidebar/recent_posts_enabled';
    public const XML_PATH_SIDEBAR_RECENT_POSTS_SORT_ORDER = 'mageos_blog/sidebar/recent_posts_sort_order';
    public const XML_PATH_SIDEBAR_RECENT_POSTS_COUNT = 'mageos_blog/sidebar/recent_posts_count';
    public const XML_PATH_SIDEBAR_CATEGORY_LIST_ENABLED = 'mageos_blog/sidebar/category_list_enabled';
    public const XML_PATH_SIDEBAR_CATEGORY_LIST_SORT_ORDER = 'mageos_blog/sidebar/category_list_sort_order';
    public const XML_PATH_SIDEBAR_TAG_CLOUD_ENABLED = 'mageos_blog/sidebar/tag_cloud_enabled';
    public const XML_PATH_SIDEBAR_TAG_CLOUD_SORT_ORDER = 'mageos_blog/sidebar/tag_cloud_sort_order';
    public const XML_PATH_SIDEBAR_ARCHIVE_ENABLED = 'mageos_blog/sidebar/archive_enabled';
    public const XML_PATH_SIDEBAR_ARCHIVE_SORT_ORDER = 'mageos_blog/sidebar/archive_sort_order';

    public const XML_PATH_SEO_OG_DEFAULT_TYPE = 'mageos_blog/seo/og_default_type';
    public const XML_PATH_SEO_JSON_LD_ENABLED = 'mageos_blog/seo/json_ld_enabled';
    public const XML_PATH_SEO_TWITTER_SITE = 'mageos_blog/seo/twitter_site';

    public const XML_PATH_SITEMAP_POST_ENABLED = 'mageos_blog/sitemap/post/enabled';
    public const XML_PATH_SITEMAP_POST_FREQUENCY = 'mageos_blog/sitemap/post/frequency';
    public const XML_PATH_SITEMAP_POST_PRIORITY = 'mageos_blog/sitemap/post/priority';
    public const XML_PATH_SITEMAP_CATEGORY_ENABLED = 'mageos_blog/sitemap/category/enabled';
    public const XML_PATH_SITEMAP_CATEGORY_FREQUENCY = 'mageos_blog/sitemap/category/frequency';
    public const XML_PATH_SITEMAP_CATEGORY_PRIORITY = 'mageos_blog/sitemap/category/priority';
    public const XML_PATH_SITEMAP_TAG_ENABLED = 'mageos_blog/sitemap/tag/enabled';
    public const XML_PATH_SITEMAP_TAG_FREQUENCY = 'mageos_blog/sitemap/tag/frequency';
    public const XML_PATH_SITEMAP_TAG_PRIORITY = 'mageos_blog/sitemap/tag/priority';

    public const XML_PATH_SOCIAL_NETWORKS = 'mageos_blog/social/networks';

    public const XML_PATH_RSS_ENABLED = 'mageos_blog/rss/enabled';
    public const XML_PATH_RSS_LIMIT = 'mageos_blog/rss/limit';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getPostsPerPage(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_POSTS_PER_PAGE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDefaultRobots(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_DEFAULT_ROBOTS, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isSidebarWidgetEnabled(string $widget, ?int $storeId = null): bool
    {
        $path = 'mageos_blog/sidebar/' . $widget . '_enabled';

        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSidebarWidgetSortOrder(string $widget, ?int $storeId = null): int
    {
        $path = 'mageos_blog/sidebar/' . $widget . '_sort_order';

        return (int) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getRecentPostsCount(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_SIDEBAR_RECENT_POSTS_COUNT, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getOgDefaultType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_SEO_OG_DEFAULT_TYPE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isJsonLdEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_SEO_JSON_LD_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getTwitterSite(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_SEO_TWITTER_SITE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isSitemapEntityEnabled(string $entity, ?int $storeId = null): bool
    {
        $path = 'mageos_blog/sitemap/' . $entity . '/enabled';

        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSitemapEntityFrequency(string $entity, ?int $storeId = null): string
    {
        $path = 'mageos_blog/sitemap/' . $entity . '/frequency';

        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSitemapEntityPriority(string $entity, ?int $storeId = null): string
    {
        $path = 'mageos_blog/sitemap/' . $entity . '/priority';

        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return string[]
     */
    public function getSocialNetworks(?int $storeId = null): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_PATH_SOCIAL_NETWORKS, ScopeInterface::SCOPE_STORE, $storeId);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    public function isRssEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_RSS_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getRssLimit(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(self::XML_PATH_RSS_LIMIT, ScopeInterface::SCOPE_STORE, $storeId);
    }
}

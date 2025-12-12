<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

/**
 * Interface SitemapConfigInterface
 */
interface SitemapConfigInterface
{
    const HOME_PAGE = 'index';
    const CATEGORIES_PAGE = 'category';
    const POSTS_PAGE = 'post';
    const TAGS_PAGE = 'tag';

    /**
     * @param $page
     * @param $storeId
     * @return bool
     */
    public function isEnabledSitemap($page, $storeId = null): bool;

    /**
     * @param $page
     * @param $storeId
     * @return string
     */
    public function getFrequency($page, $storeId = null): string;

    /**
     * @param $page
     * @param $storeId
     * @return float
     */
    public function getPriority($page, $storeId = null): float;
}

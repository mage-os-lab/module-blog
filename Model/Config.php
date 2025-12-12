<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * MageOS Blog Config Model
 */
class Config
{
    /**
     * MODULE SYSTEM KEY
     */
    CONST string MODULE_SYS_KEY = "mageos_blog";

    /**
     * SYSTEM CONFIGURATION SECTIONS
     */
    CONST string SYS_GENERAL   = "general";
    CONST string SYS_POST      = "post";
    CONST string SYS_SIDEBAR   = "sidebar";
    CONST string SYS_INDEX     = "index_page";
    CONST string SYS_LIST      = "post_list";
    CONST string SYS_TAG       = "tag";
    CONST string SYS_DESIGN    = "design";
    CONST string SYS_SEARCH    = "search";
    CONST string SYS_PRODUCT   = "product_page";
    CONST string SYS_PERMALINK = "permalink";
    CONST string SYS_SEO       = "seo";
    CONST string SYS_SITEMAP   = "sitemap";
    CONST string SYS_SOCIAL    = "social";
    CONST string SYS_MENU      = "top_menu";
    CONST string SYS_DEV       = "developer";

    /**
     * SYSTEM CONFIGURATION GENERAL FIELDS
     */
    CONST string XML_PATH_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_GENERAL.'/'. "enabled";

    /**
     * SYSTEM CONFIGURATION POST FIELDS
     */
    CONST string XML_PATH_POST_LAYOUT = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "layout";
    CONST string XML_PATH_POST_VIEW_COUNT = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "view_counts";
    CONST string XML_PATH_POST_READING_TIME = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "reading_time";
    CONST string XML_PATH_POST_NEXTPREV = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "nextprev";
    CONST string XML_PATH_POST_RELATED_CATEGORY = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "related_category";
    CONST string XML_PATH_POST_RELATED_POSTS = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "related_posts";
    CONST string XML_PATH_POST_RELATED_PRODUCTS = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/'. "related_products";
    // POST COMMENTS SUBGROUP
    CONST string XML_PATH_COMMENTS_GUEST_COMMENTS = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/comments/'. "guest_comments";
    CONST string XML_PATH_COMMENTS_DEFAULT_STATUS = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/comments/'. "default_status";
    CONST string XML_PATH_COMMENTS_FORMAT_DATE = self::MODULE_SYS_KEY.'/'.self::SYS_POST.'/comments/'. "format_date";

    /**
     * SYSTEM CONFIGURATION SIDEBAR FIELDS
     */
    CONST string XML_PATH_SIDEBAR_SEARCH_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/search/'. "enabled";
    CONST string XML_PATH_SIDEBAR_SEARCH_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/search/'. "sort_order";

    CONST string XML_PATH_SIDEBAR_CONTENTS_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/contents/'. "enabled";
    CONST string XML_PATH_SIDEBAR_CONTENTS_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/contents/'. "sort_order";

    CONST string XML_PATH_SIDEBAR_CATEGORIES_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/categories/'. "enabled";
    CONST string XML_PATH_SIDEBAR_CATEGORIES_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/categories/'. "sort_order";

    CONST string XML_PATH_SIDEBAR_RECENT_POSTS_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/recent_posts/'. "enabled";
    CONST string XML_PATH_SIDEBAR_RECENT_POSTS_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/recent_posts/'. "sort_order";

    CONST string XML_PATH_SIDEBAR_POPULAR_POSTS_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/popular_posts/'. "enabled";
    CONST string XML_PATH_SIDEBAR_POPULAR_POSTS_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/popular_posts/'. "sort_order";

    CONST string XML_PATH_SIDEBAR_POST_RELATED_PRODUCTS_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/post_related_products/'. "enabled";
    CONST string XML_PATH_SIDEBAR_POST_RELATED_PRODUCTS_SORT_ORDER = self::MODULE_SYS_KEY.'/'.self::SYS_SIDEBAR.'/post_related_products/'. "sort_order";

    /**
     * SYSTEM CONFIGURATION INDEX PAGE FIELDS
     */
    CONST string XML_PATH_INDEX_PAGE_TITLE = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "title";
    CONST string XML_PATH_INDEX_PAGE_LAYOUT = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "index_page_layout";
    CONST string XML_PATH_INDEX_PAGE_DISPLAY_MODE = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "display_mode";
    CONST string XML_PATH_INDEX_PAGE_POST_IDS = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "post_ids";
    CONST string XML_PATH_INDEX_PAGE_POSTS_SORT_BY = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "posts_sort_by";
    CONST string XML_PATH_INDEX_PAGE_META_TITLE = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "meta_title";
    CONST string XML_PATH_INDEX_PAGE_META_KEYWORDS = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "meta_keywords";
    CONST string XML_PATH_INDEX_PAGE_META_DESCRIPTION = self::MODULE_SYS_KEY.'/'.self::SYS_INDEX.'/'. "meta_description";

    /**
     * SYSTEM CONFIGURATION POST LIST FIELDS
     */
    CONST string XML_PATH_LIST_POSTS_PER_PAGE = self::MODULE_SYS_KEY.'/'.self::SYS_LIST.'/'. "posts_per_page";
    CONST string XML_PATH_LIST_SHORTCONTENT_LENGTH = self::MODULE_SYS_KEY.'/'.self::SYS_LIST.'/'. "shortcotent_length";

    /**
     * SYSTEM CONFIGURATION TAG FIELDS
     */
    CONST string XML_PATH_TAG_ROBOTS = self::MODULE_SYS_KEY.'/'.self::SYS_TAG.'/'. "robots";
    CONST string XML_PATH_TAG_PAGE_LAYOUT = self::MODULE_SYS_KEY.'/'.self::SYS_TAG.'/'. "tag_page_layout";

    /**
     * SYSTEM CONFIGURATION DESIGN FIELDS
     */
    CONST string XML_PATH_DESIGN_CATEGORY_PAGE_LAYOUT = self::MODULE_SYS_KEY.'/'.self::SYS_DESIGN.'/'. "category_page_layout";
    CONST string XML_PATH_DESIGN_PUBLICATION_DATE = self::MODULE_SYS_KEY.'/'.self::SYS_DESIGN.'/'. "publication_date";
    CONST string XML_PATH_DESIGN_FORMAT_DATE = self::MODULE_SYS_KEY.'/'.self::SYS_DESIGN.'/'. "format_date";


    /**
     * SYSTEM CONFIGURATION SEARCH FIELDS
     */
    CONST string XML_PATH_SEARCH_ROBOTS = self::MODULE_SYS_KEY.'/'.self::SYS_SEARCH.'/'. "robots";
    CONST string XML_PATH_SEARCH_PAGE_LAYOUT = self::MODULE_SYS_KEY.'/'.self::SYS_SEARCH.'/'. "search_page_layout";
    CONST string XML_PATH_SEARCH_ENABLE = self::MODULE_SYS_KEY . '/' . self::SYS_SEARCH . '/enable_blog_search';

    /**
     * SYSTEM CONFIGURATION PRODUCT PAGE FIELDS
     */
    CONST string XML_PATH_PRODUCT_PAGE_RELATED_POSTS_ENABLED = self::MODULE_SYS_KEY.'/'.self::SYS_PRODUCT.'/'. "related_posts_enabled";
    CONST string XML_PATH_PRODUCT_PAGE_NUMBER_OF_RELATED_POSTS = self::MODULE_SYS_KEY.'/'.self::SYS_PRODUCT.'/'. "number_of_related_posts";

    /**
     * SYSTEM CONFIGURATION PERMALINK FIELDS
     */
    CONST string XML_PATH_PERMALINK_ROUTE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "route";
    CONST string XML_PATH_PERMALINK_REDIRECT_TO_NO_SLASH = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "redirect_to_no_slash";
    CONST string XML_PATH_PERMALINK_TYPE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "type";
    CONST string XML_PATH_PERMALINK_POST_ROUTE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "post_route";
    CONST string XML_PATH_PERMALINK_POST_SUFIX = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "post_sufix";
    CONST string XML_PATH_PERMALINK_POST_USE_CATEGORIES = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "post_use_categories";
    CONST string XML_PATH_PERMALINK_CATEGORY_ROUTE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "category_route";
    CONST string XML_PATH_PERMALINK_CATEGORY_FUFIX = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "category_sufix";
    CONST string XML_PATH_PERMALINK_CATEGORY_USE_CATEGORIES = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "category_use_categories";
    CONST string XML_PATH_PERMALINK_TAG_ROUTE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "tag_route";
    CONST string XML_PATH_PERMALINK_TAG_SUFIX = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "tag_sufix";
    CONST string XML_PATH_PERMALINK_SEARCH_ROUTE = self::MODULE_SYS_KEY.'/'.self::SYS_PERMALINK.'/'. "search_route";


    /**
     * SYSTEM CONFIGURATION SEO FIELDS
     */
    CONST string XML_PATH_SEO_USE_CANONICAL_META_TAG_FOR = self::MODULE_SYS_KEY.'/'.self::SYS_SEO.'/'. "use_canonical_meta_tag_for";

    /**
     * SYSTEM CONFIGURATION SITEMAP FIELDS
     */
    CONST string XML_PATH_SITEMAP_INDEX_ENABLE  = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/index/'. "enabled";
    CONST string XML_PATH_SITEMAP_CATEGORY_ENABLE  = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/category/'. "enabled";
    CONST string XML_PATH_SITEMAP_POST_ENABLE  = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/post/'. "enabled";
    CONST string XML_PATH_SITEMAP_TAG_ENABLE  = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/tag/'. "enabled";

    CONST string XML_PATH_SITEMAP_INDEX_FREQUENCY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/index/'. "frequency";
    CONST string XML_PATH_SITEMAP_CATEGORY_FREQUENCY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/category/'. "frequency";
    CONST string XML_PATH_SITEMAP_POST_FREQUENCY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/post/'. "frequency";
    CONST string XML_PATH_SITEMAP_TAG_FREQUENCY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/tag/'. "frequency";

    CONST string XML_PATH_SITEMAP_INDEX_PRIORITY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/index/'. "priority";
    CONST string XML_PATH_SITEMAP_CATEGORY_PRIORITY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/category/'. "priority";
    CONST string XML_PATH_SITEMAP_POST_PRIORITY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/post/'. "priority";
    CONST string XML_PATH_SITEMAP_TAG_PRIORITY = self::MODULE_SYS_KEY.'/'.self::SYS_SITEMAP.'/tag/'. "priority";

    /**
     * SYSTEM CONFIGURATION SOCIAL FIELDS
     */
    CONST string XML_PATH_SOCIAL_SHARE_ENABLED = self::MODULE_SYS_KEY . '/' . self::SYS_SOCIAL . '/add_this_enabled';
    CONST string XML_PATH_SOCIAL_NETWORKS = self::MODULE_SYS_KEY . '/' . self::SYS_SOCIAL . '/use_social_networks';

    /**
     * SYSTEM CONFIGURATION TOP MENU FIELDS
     */
    CONST string XML_PATH_MENU_DISPLAY_BLOG_LINK = self::MODULE_SYS_KEY . '/' . self::SYS_MENU . '/show_item';
    CONST string XML_PATH_MENU_LINK_TEXT = self::MODULE_SYS_KEY . '/' . self::SYS_MENU . '/item_text';
    CONST string XML_PATH_MENU_INCLUDE_BLOG_CATEGORIES = self::MODULE_SYS_KEY . '/' . self::SYS_MENU . '/include_categories';
    CONST string XML_PATH_MENU_MAX_DEPTH = self::MODULE_SYS_KEY . '/' . self::SYS_MENU . '/max_depth';

    /**
     * SYSTEM CONFIGURATION DEVELOPER FIELDS
     */
    CONST string XML_PATH_DEV_CSS_INCLUDE_ALL_PAGES = self::MODULE_SYS_KEY . '/' . self::SYS_DEV . '/css_settings/include_all_pages';
    CONST string XML_PATH_DEV_CSS_INCLUDE_HOME_PAGE = self::MODULE_SYS_KEY . '/' . self::SYS_DEV . '/css_settings/include_home_page';
    CONST string XML_PATH_DEV_CSS_INCLUDE_PRODUCT_PAGE = self::MODULE_SYS_KEY . '/' . self::SYS_DEV . '/css_settings/include_product_page';
    CONST string XML_PATH_DEV_CSS_CUSTOM_CSS = self::MODULE_SYS_KEY . '/' . self::SYS_DEV . '/css_settings/custom_css';
    CONST string XML_PATH_DEV_CSS_INCLUDE_BOOTSTRAP_CUSTOM_MINI = self::MODULE_SYS_KEY . '/' . self::SYS_DEV . '/css_settings/include_bootstrap_custom_mini';

    /**
     * GLOBAL VARIABLES CONFIGURATIONS
     */
    CONST int MAX_NUMBER_OF_COMMENTS = 5;
    CONST int MAX_NUMBER_OF_REPLIES = 5;
    CONST int MAX_NUMBER_OF_RELATED_POSTS = 5;
    CONST int MAX_NUMBER_OF_RELATED_PRODUCTS = 5;
    CONST int MAX_NUMBER_OF_RECENT_POSTS = 5;
    const string CANONICAL_PAGE_TYPE_NONE = 'none';
    const string CANONICAL_PAGE_TYPE_ALL = 'all';
    const string CANONICAL_PAGE_TYPE_INDEX = 'index';
    const string CANONICAL_PAGE_TYPE_POST = 'post';
    const string CANONICAL_PAGE_TYPE_CATEGORY = 'category';
    const string CANONICAL_PAGE_TYPE_TAG = 'tag';
    CONST string CUSTOM_BOOTSTRAP_CSS = 'bootstrap-4.4.1-custom-min.css';


    /**
     * Magento Constants
     */
    const string SCOPE_STORE = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;


    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve tag page robots
     *
     * @return string
     */
    public function getTagRobots($storeId = null): string
    {
        return $this->getConfig(
            self::XML_PATH_TAG_ROBOTS,
            $storeId
        );
    }

    /**
     * Retrieve search page robots
     *
     * @return string
     */
    public function getSearchRobots($storeId = null): string
    {
        return $this->getConfig(
            self::XML_PATH_SEARCH_ROBOTS,
            $storeId
        );
    }

    /**
     * Retrieve true if blog module is enabled
     *
     * @return bool
     */
    public function isEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_ENABLED,
            $storeId
        );
    }

    /**
     * Retrieve true if blog related posts are enabled
     *
     * @return bool
     */
    public function isRelatedPostsEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_POST_RELATED_POSTS,
            $storeId
        );
    }

    /**
     * Retrieve true if blog related products are enabled
     *
     * @return bool
     */
    public function isRelatedProductsEnabled($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_POST_RELATED_PRODUCTS,
            $storeId
        );
    }

    /**
     * Retrieve store config value
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null): mixed
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $pageType
     * @return bool
     */
    public function getDisplayCanonicalTag($pageType): bool
    {

        if ($this->getConfig(self::XML_PATH_SEO_USE_CANONICAL_META_TAG_FOR)) {
            $displayFor = explode(',', $this->getConfig(self::XML_PATH_SEO_USE_CANONICAL_META_TAG_FOR));
        } else {
            $displayFor = [];
        }

        return in_array($pageType, $displayFor) || in_array(self::CANONICAL_PAGE_TYPE_ALL, $displayFor) ? true : false;
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isBlogCssIncludeOnAll($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_DEV_CSS_INCLUDE_ALL_PAGES,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isBlogCssIncludeOnHome($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_DEV_CSS_INCLUDE_HOME_PAGE,
            $storeId
        );
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isBlogCssIncludeOnProduct($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_DEV_CSS_INCLUDE_PRODUCT_PAGE,
            $storeId
        );
    }

    /**
     * @param $storeId
     * @return string
     */
    public function getCustomCss($storeId = null): string
    {
        return (string)$this->getConfig(self::XML_PATH_DEV_CSS_CUSTOM_CSS, $storeId);
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function getIncludeBootstrapCustomMini($storeId = null): bool
    {
        return (bool)$this->getConfig(
            self::XML_PATH_DEV_CSS_INCLUDE_BOOTSTRAP_CUSTOM_MINI,
            $storeId
        );
    }

    /**
     * Retrieve translated & formated date
     * @param string $format
     * @param $dateOrTime
     * @return string
     */
    public static function getTranslatedDate(string $format, $dateOrTime): string
    {
        $time = is_numeric($dateOrTime) ? $dateOrTime : strtotime((string)$dateOrTime);
        $month = ['F' => '%1', 'M' => '%2'];

        foreach ($month as $from => $to) {
            $format = str_replace($from, $to, $format);
        }

        $date = date($format, $time);

        foreach ($month as $to => $from) {
            $date = str_replace($from, date($to, $time), $date);
        }

        return $date;
    }
}

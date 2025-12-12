<?php
declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Api\ShortContentExtractorInterface;

interface BlogCategoryInterface
{
    const CATEGORY_ID = 'category_id';
    const IS_ACTIVE = 'is_active';
    const POSITION = 'position';
    const TITLE = 'title';
    const CONTENT_HEADING = 'content_heading';
    const IDENTIFIER = 'identifier';
    const PATH = 'path';
    const PAGE_LAYOUT = 'page_layout';
    const CUSTOM_THEME = 'custom_theme';
    const CUSTOM_LAYOUT = 'custom_layout';
    const LAYOUT_UPDATE_XML = 'layout_update_xml';
    const CUSTOM_LAYOUT_UPDATE_XML = 'custom_layout_update_xml';
    const CONTENT = 'content';
    const META_KEYWORDS = 'meta_keywords';
    const META_DESCRIPTION = 'meta_description';
    const CUSTOM_THEME_FROM = 'custom_theme_from';
    const CUSTOM_THEME_TO = 'custom_theme_to';
    const POSTS_PER_PAGE = 'posts_per_page';
    const POSTS_LIST_TEMPLATE = 'posts_list_template';
    const POSTS_SORT_BY = 'posts_sort_by';
    const DISPLAY_MODE = 'display_mode';
    const META_TITLE = 'meta_title';
    const INCLUDE_IN_MENU = 'include_in_menu';
    const META_ROBOTS = 'meta_robots';
    const INCLUDE_IN_SIDEBAR_TREE = 'include_in_sidebar_tree';

    /**
    * Retrieve identities
    * @return array
    */
    public function getIdentities(): array;

    /**
     * Retrieve controller name
     * @return string
     */
    public function getControllerName(): string;

    /**
     * Retrieve model title
     * @param boolean $plural
     * @return string
     */
    public function getOwnTitle(bool $plural = false): string;

    /**
     * Check if category identifier exist for specific store
     * return category id if category exists
     * @param string $identifier
     * @param int $storeId
     * @return string
     * @throws LocalizedException
     */
    public function checkIdentifier(string $identifier, int $storeId): string;

    /**
     * Get category_id
     * @return int|null
     */
    public function getCategoryId(): ?int;

    /**
     * Set category_id
     * @param int $categoryId
     * @return BlogCategoryInterface
     */
    public function setCategoryId(int $categoryId): self;

    /**
     * Get is_active
     * @return int|null
     */
    public function getIsActive(): ?int;

    /**
     * Set is_active
     * @param int $isActive
     * @return BlogCategoryInterface
     */
    public function setIsActive(int $isActive): self;

    /**
     * Get position
     * @return int|null
     */
    public function getPosition(): ?int;

    /**
     * Set position
     * @param int $position
     * @return BlogCategoryInterface
     */
    public function setPosition(int $position): self;

    /**
     * Get title
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Set title
     * @param string $title
     * @return BlogCategoryInterface
     */
    public function setTitle(string $title): self;

    /**
     * Get content_heading
     * @return string|null
     */
    public function getContentHeading(): ?string;

    /**
     * Set content_heading
     * @param string $contentHeading
     * @return BlogCategoryInterface
     */
    public function setContentHeading(string $contentHeading): self;

    /**
     * Get identifier
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * Set identifier
     * @param string $identifier
     * @return BlogCategoryInterface
     */
    public function setIdentifier(string $identifier): self;

    /**
     * Get path
     * Need to set the return type as string, but we are getting an int from the database
     * @TODO: debug what we get from the database at line 77 Model/Import/Wordpress.php
     * @return string|null
     */
    public function getPath();

    /**
     * Set path
     * @param string $path
     * @return BlogCategoryInterface
     */
    public function setPath(string $path): self;

    /**
     * Get page_layout
     * @return string|null
     */
    public function getPageLayout(): ?string;

    /**
     * Set page_layout
     * @param string $pageLayout
     * @return BlogCategoryInterface
     */
    public function setPageLayout(string $pageLayout): self;

    /**
     * Get custom_theme
     * @return string|null
     */
    public function getCustomTheme(): ?string;

    /**
     * Set custom_theme
     * @param string $customTheme
     * @return BlogCategoryInterface
     */
    public function setCustomTheme(string $customTheme): self;

    /**
     * Get custom_layout
     * @return string|null
     */
    public function getCustomLayout(): ?string;

    /**
     * Set custom_layout
     * @param string $customLayout
     * @return BlogCategoryInterface
     */
    public function setCustomLayout(string $customLayout): self;

    /**
     * Get layout_update_xml
     * @return string|null
     */
    public function getLayoutUpdateXml(): ?string;

    /**
     * Set layout_update_xml
     * @param string $layoutUpdateXml
     * @return BlogCategoryInterface
     */
    public function setLayoutUpdateXml(string $layoutUpdateXml): self;

    /**
     * Get custom_layout_update_xml
     * @return string|null
     */
    public function getCustomLayoutUpdateXml(): ?string;

    /**
     * Set custom_layout_update_xml
     * @param string $customLayoutUpdateXml
     * @return BlogCategoryInterface
     */
    public function setCustomLayoutUpdateXml(string $customLayoutUpdateXml): self;

    /**
     * Get content
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * Set content
     * @param string $content
     * @return BlogCategoryInterface
     */
    public function setContent(string $content): self;

    /**
     * Get meta_keywords
     * @return string|null
     */
    public function getMetaKeywords(): ?string;

    /**
     * Set meta_keywords
     * @param string $metaKeywords
     * @return BlogCategoryInterface
     */
    public function setMetaKeywords(string $metaKeywords): self;

    /**
     * Get meta_description
     * @return string|null
     */
    public function getMetaDescription(): ?string;

    /**
     * Set meta_description
     * @param string $metaDescription
     * @return BlogCategoryInterface
     */
    public function setMetaDescription(string $metaDescription): self;

    /**
     * Get custom_theme_from
     * @return string|null
     */
    public function getCustomThemeFrom(): ?string;

    /**
     * Set custom_theme_from
     * @param string $customThemeFrom
     * @return BlogCategoryInterface
     */
    public function setCustomThemeFrom(string $customThemeFrom): self;

    /**
     * Get custom_theme_to
     * @return string|null
     */
    public function getCustomThemeTo(): ?string;

    /**
     * Set custom_theme_to
     * @param string $customThemeTo
     * @return BlogCategoryInterface
     */
    public function setCustomThemeTo(string $customThemeTo): self;

    /**
     * Get posts_per_page
     * @return int|null
     */
    public function getPostsPerPage(): ?int;

    /**
     * Set posts_per_page
     * @param int $postsPerPage
     * @return BlogCategoryInterface
     */
    public function setPostsPerPage(int $postsPerPage): self;

    /**
     * Get posts_list_template
     * @return string|null
     */
    public function getPostsListTemplate(): ?string;

    /**
     * Set posts_list_template
     * @param string $postsListTemplate
     * @return BlogCategoryInterface
     */
    public function setPostsListTemplate(string $postsListTemplate): self;

    /**
     * Get posts_sort_by
     * @return int|null
     */
    public function getPostsSortBy(): ?int;

    /**
     * Set posts_sort_by
     * @param int $postsSortBy
     * @return BlogCategoryInterface
     */
    public function setPostsSortBy(int $postsSortBy): self;

    /**
     * Get display_mode
     * @return int|null
     */
    public function getDisplayMode(): ?int;

    /**
     * Set display_mode
     * @param int $displayMode
     * @return BlogCategoryInterface
     */
    public function setDisplayMode(int $displayMode): self;

    /**
     * Get meta_title
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * Set meta_title
     * @param string $metaTitle
     * @return BlogCategoryInterface
     */
    public function setMetaTitle(string $metaTitle): self;

    /**
     * Get include_in_menu
     * @return int|null
     */
    public function getIncludeInMenu(): ?int;

    /**
     * Set include_in_menu
     * @param int $includeInMenu
     * @return BlogCategoryInterface
     */
    public function setIncludeInMenu(int $includeInMenu): self;

    /**
     * Get meta_robots
     * @return string|null
     */
    public function getMetaRobots(): ?string;

    /**
     * Set meta_robots
     * @param string $metaRobots
     * @return BlogCategoryInterface
     */
    public function setMetaRobots(string $metaRobots): self;

    /**
     * Get include_in_sidebar_tree
     * @return string|null
     */
    public function getIncludeInSidebarTree(): ?string;

    /**
     * Set include_in_sidebar_tree
     * @param string $includeInSidebarTree
     * @return BlogCategoryInterface
     */
    public function setIncludeInSidebarTree(string $includeInSidebarTree): self;
}

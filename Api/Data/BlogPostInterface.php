<?php

namespace MageOS\Blog\Api\Data;

interface BlogPostInterface
{
    const POST_ID = 'post_id';
    const COMMENTS_COUNT = 'comments_count';
    const READING_TIME = 'reading_time';
    const VIEWS_COUNT = 'views_count';
    const IS_RECENT_POSTS_SKIP = 'is_recent_posts_skip';
    const IS_ACTIVE = 'is_active';
    const STRUCTURE_DATA_TYPE = 'structure_data_type';
    const POSITION = 'position';
    const INCLUDE_IN_RECENT = 'include_in_recent';
    const TITLE = 'title';
    const IDENTIFIER = 'identifier';
    const CONTENT_HEADING = 'content_heading';
    const FEATURED_IMG = 'featured_img';
    const PAGE_LAYOUT = 'page_layout';
    const CUSTOM_THEME = 'custom_theme';
    const META_TITLE = 'meta_title';
    const OG_TYPE = 'og_type';
    const OG_IMG = 'og_img';
    const OG_DESCRIPTION = 'og_description';
    const OG_TITLE = 'og_title';
    const SECRET = 'secret';
    const FEATURED_IMG_ALT = 'featured_img_alt';
    const CUSTOM_LAYOUT_UPDATE_XML = 'custom_layout_update_xml';
    const LAYOUT_UPDATE_XML = 'layout_update_xml';
    const CONTENT = 'content';
    const SHORT_CONTENT = 'short_content';
    const MEDIA_GALLERY = 'media_gallery';
    const META_KEYWORDS = 'meta_keywords';
    const META_DESCRIPTION = 'meta_description';
    const META_ROBOTS = 'meta_robots';
    const CREATION_TIME = 'creation_time';
    const UPDATE_TIME = 'update_time';
    const PUBLISH_TIME = 'publish_time';
    const CUSTOM_THEME_FROM = 'custom_theme_from';
    const CUSTOM_THEME_TO = 'custom_theme_to';

    /**
     * Get post_id
     * @return int|null
     */
    public function getPostId(): ?int;

    /**
     * Set post_id
     * @param int $postId
     * @return BlogPostInterface
     */
    public function setPostId(int $postId): self;

    /**
     * Get comments_count
     * @return int|null
     */
    public function getCommentsCount(): ?int;

    /**
     * Set comments_count
     * @param int $commentsCount
     * @return BlogPostInterface
     */
    public function setCommentsCount(int $commentsCount): self;

    /**
     * Get reading_time
     * @return int|null
     */
    public function getReadingTime(): ?int;

    /**
     * Set reading_time
     * @param int $readingTime
     * @return BlogPostInterface
     */
    public function setReadingTime(int $readingTime): self;

    /**
     * Get views_count
     * @return int|null
     */
    public function getViewsCount(): ?int;

    /**
     * Set views_count
     * @param int $viewsCount
     * @return BlogPostInterface
     */
    public function setViewsCount(int $viewsCount): self;

    /**
     * Get is_recent_posts_skip
     * @return int|null
     */
    public function getIsRecentPostsSkip(): ?int;

    /**
     * Set is_recent_posts_skip
     * @param int $isRecentPostsSkip
     * @return BlogPostInterface
     */
    public function setIsRecentPostsSkip(int $isRecentPostsSkip): self;

    /**
     * Get is_active
     * @return int|null
     */
    public function getIsActive(): ?int;

    /**
     * Set is_active
     * @param int $isActive
     * @return BlogPostInterface
     */
    public function setIsActive(int $isActive): self;

    /**
     * Get structure_data_type
     * @return int|null
     */
    public function getStructureDataType(): ?int;

    /**
     * Set structure_data_type
     * @param int $structureDataType
     * @return BlogPostInterface
     */
    public function setStructureDataType(int $structureDataType): self;

    /**
     * Get position
     * @return int|null
     */
    public function getPosition(): ?int;

    /**
     * Set position
     * @param int $position
     * @return BlogPostInterface
     */
    public function setPosition(int $position): self;

    /**
     * Get include_in_recent
     * @return int|null
     */
    public function getIncludeInRecent(): ?int;

    /**
     * Set include_in_recent
     * @param int $includeInRecent
     * @return BlogPostInterface
     */
    public function setIncludeInRecent(int $includeInRecent): self;

    /**
     * Get title
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Set title
     * @param string $title
     * @return BlogPostInterface
     */
    public function setTitle(string $title): self;

    /**
     * Get identifier
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * Set identifier
     * @param string $identifier
     * @return BlogPostInterface
     */
    public function setIdentifier(string $identifier): self;

    /**
     * Get content_heading
     * @return string|null
     */
    public function getContentHeading(): ?string;

    /**
     * Set content_heading
     * @param string $contentHeading
     * @return BlogPostInterface
     */
    public function setContentHeading(string $contentHeading): self;

    /**
     * Get featured_img
     * @return string|null
     */
    public function getFeaturedImg(): ?string;

    /**
     * Set featured_img
     * @param string $featuredImg
     * @return BlogPostInterface
     */
    public function setFeaturedImg(string $featuredImg): self;

    /**
     * Get page_layout
     * @return string|null
     */
    public function getPageLayout(): ?string;

    /**
     * Set page_layout
     * @param string $pageLayout
     * @return BlogPostInterface
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
     * @return BlogPostInterface
     */
    public function setCustomTheme(string $customTheme): self;

    /**
     * Get meta_title
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * Set meta_title
     * @param string $metaTitle
     * @return BlogPostInterface
     */
    public function setMetaTitle(string $metaTitle): self;

    /**
     * Get og_type
     * @return string|null
     */
    public function getOgType(): ?string;

    /**
     * Set og_type
     * @param string $ogType
     * @return BlogPostInterface
     */
    public function setOgType(string $ogType): self;

    /**
     * Get og_img
     * @return string|null
     */
    public function getOgImg(): ?string;

    /**
     * Set og_img
     * @param string $ogImg
     * @return BlogPostInterface
     */
    public function setOgImg(string $ogImg): self;

    /**
     * Get og_description
     * @return string|null
     */
    public function getOgDescription(): ?string;

    /**
     * Set og_description
     * @param string $ogDescription
     * @return BlogPostInterface
     */
    public function setOgDescription(string $ogDescription): self;

    /**
     * Get og_title
     * @return string|null
     */
    public function getOgTitle(): ?string;

    /**
     * Set og_title
     * @param string $ogTitle
     * @return BlogPostInterface
     */
    public function setOgTitle(string $ogTitle): self;

    /**
     * Get secret
     * @return string|null
     */
    public function getSecret(): ?string;

    /**
     * Set secret
     * @param string $secret
     * @return BlogPostInterface
     */
    public function setSecret(string $secret): self;

    /**
     * Get featured_img_alt
     * @return string|null
     */
    public function getFeaturedImgAlt(): ?string;

    /**
     * Set featured_img_alt
     * @param string $featuredImgAlt
     * @return BlogPostInterface
     */
    public function setFeaturedImgAlt(string $featuredImgAlt): self;

    /**
     * Get custom_layout_update_xml
     * @return string|null
     */
    public function getCustomLayoutUpdateXml(): ?string;

    /**
     * Set custom_layout_update_xml
     * @param string $customLayoutUpdateXml
     * @return BlogPostInterface
     */
    public function setCustomLayoutUpdateXml(string $customLayoutUpdateXml): self;

    /**
     * Get layout_update_xml
     * @return string|null
     */
    public function getLayoutUpdateXml(): ?string;

    /**
     * Set layout_update_xml
     * @param string $layoutUpdateXml
     * @return BlogPostInterface
     */
    public function setLayoutUpdateXml(string $layoutUpdateXml): self;

    /**
     * Get content
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * Set content
     * @param string $content
     * @return BlogPostInterface
     */
    public function setContent(string $content): self;

    /**
     * Get short_content
     * @return string|null
     */
    public function getShortContent(): ?string;

    /**
     * Set short_content
     * @param string $shortContent
     * @return BlogPostInterface
     */
    public function setShortContent(string $shortContent): self;

    /**
     * Get media_gallery
     * @return string|null
     */
    public function getMediaGallery(): ?string;

    /**
     * Set media_gallery
     * @param string $mediaGallery
     * @return BlogPostInterface
     */
    public function setMediaGallery(string $mediaGallery): self;

    /**
     * Get meta_keywords
     * @return string|null
     */
    public function getMetaKeywords(): ?string;

    /**
     * Set meta_keywords
     * @param string $metaKeywords
     * @return BlogPostInterface
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
     * @return BlogPostInterface
     */
    public function setMetaDescription(string $metaDescription): self;

    /**
     * Get meta_robots
     * @return string|null
     */
    public function getMetaRobots(): ?string;

    /**
     * Set meta_robots
     * @param string $metaRobots
     * @return BlogPostInterface
     */
    public function setMetaRobots(string $metaRobots): self;

    /**
     * Get creation_time
     * @return string|null
     */
    public function getCreationTime(): ?string;

    /**
     * Set creation_time
     * @param string $creationTime
     * @return BlogPostInterface
     */
    public function setCreationTime(string $creationTime): self;

    /**
     * Get update_time
     * @return string|null
     */
    public function getUpdateTime(): ?string;

    /**
     * Set update_time
     * @param string $updateTime
     * @return BlogPostInterface
     */
    public function setUpdateTime(string $updateTime): self;

    /**
     * Get publish_time
     * @return string|null
     */
    public function getPublishTime(): ?string;

    /**
     * Set publish_time
     * @param string $publishTime
     * @return BlogPostInterface
     */
    public function setPublishTime(string $publishTime): self;

    /**
     * Get custom_theme_from
     * @return string|null
     */
    public function getCustomThemeFrom(): ?string;

    /**
     * Set custom_theme_from
     * @param string $customThemeFrom
     * @return BlogPostInterface
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
     * @return BlogPostInterface
     */
    public function setCustomThemeTo(string $customThemeTo): self;
}

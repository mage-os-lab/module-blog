<?php
declare(strict_types=1);

namespace MageOS\Blog\Block;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Block\Post\PostList;
use MageOS\Blog\Model\Config\Source\PostsSortBy;
use MageOS\Blog\Block\Post\PostList\Toolbar;
use MageOS\Blog\Model\Config;

class Index extends PostList
{
    /**
     * Preparing global layout
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout(): static
    {
        $this->_addBreadcrumbs();
        $this->pageConfig->getTitle()->set(
            $this->_getConfigValue('meta_title') ?: $this->_getConfigValue('title')
        );
        $this->pageConfig->setKeywords($this->_getConfigValue('meta_keywords'));
        $this->pageConfig->setDescription($this->_getConfigValue('meta_description'));

        if ($this->config->getDisplayCanonicalTag(Config::CANONICAL_PAGE_TYPE_INDEX)) {

            $canonicalUrl = $this->_url->getBaseUrl();
            $page = (int)$this->_request->getParam($this->getPageParamName());
            if ($page > 1) {
                $canonicalUrl .= ((false === strpos($canonicalUrl, '?')) ? '?' : '&')
                    . $this->getPageParamName() . '=' . $page;
            }

            $this->pageConfig->addRemotePageAsset(
                $canonicalUrl,
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );
        }

        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle(
                $this->escapeHtml($this->_getConfigValue('title'))
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve Toolbar Block
     * @return \MageOS\Blog\Block\Post\PostList\Toolbar
     */
    public function getToolbarBlock(): Toolbar
    {
        $toolBarBlock = parent::getToolbarBlock();
        $limit = false; // We can set a limit to the nr of post to show on the blog page.

        if ($limit) {
            $toolBarBlock->setData('limit', $limit);
        }

        return $toolBarBlock;
    }

    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function _preparePostCollection(): void
    {
        parent::_preparePostCollection();

        $displayMode = $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_DISPLAY_MODE,
            Config::SCOPE_STORE
        );
        /* If featured posts enabled */
        if ($displayMode == 1) {
            $postIds = $this->_scopeConfig->getValue(
                Config::XML_PATH_INDEX_PAGE_POST_IDS,
                Config::SCOPE_STORE
            );
            $this->_postCollection->addPostsFilter($postIds);
        } else {
            $this->_postCollection->addRecentFilter();
        }
    }

     /**
      * Retrieve collection order field
      *
      * @return string
      */
    public function getCollectionOrderField(): string
    {
        $postsSortBy = $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_POSTS_SORT_BY,
            Config::SCOPE_STORE
        );

        switch ($postsSortBy) {
            case PostsSortBy::POSITION:
                return self::POSTS_SORT_FIELD_BY_POSITION;
            case PostsSortBy::TITLE:
                return self::POSTS_SORT_FIELD_BY_TITLE;
            default:
                return parent::getCollectionOrderField();
        }
    }

    /**
     * Retrieve collection order direction
     *
     * @return string
     */
    public function getCollectionOrderDirection(): string
    {
        $postsSortBy = $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_POSTS_SORT_BY,
            Config::SCOPE_STORE
        );

        if (PostsSortBy::TITLE == $postsSortBy) {
            return SortOrder::SORT_ASC;
        }
        return parent::getCollectionOrderDirection();
    }

    /**
     * Retrieve blog title
     * @param $param
     * @return string|null
     */
    protected function _getConfigValue($param): ?string
    {
        return $this->_scopeConfig->getValue(
            Config::MODULE_SYS_KEY .'/'.Config::SYS_INDEX.'/'.$param,
            Config::SCOPE_STORE
        );
    }

    /**
     * Prepare breadcrumbs
     *
     * @param  string $title
     * @param  string $key
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function _addBreadcrumbs($title = null, $key = null): void
    {
        if ($breadcrumbsBlock = $this->getBreadcrumbsBlock()) {
            $breadcrumbsBlock->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            );

            $blogTitle = $this->_scopeConfig->getValue(
                Config::XML_PATH_INDEX_PAGE_TITLE,
                Config::SCOPE_STORE
            );
            $breadcrumbsBlock->addCrumb(
                'blog',
                [
                    'label' => __($blogTitle),
                    'title' => __($blogTitle),
                    'link' => null,
                ]
            );
        }
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        $displayMode = $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_DISPLAY_MODE,
            Config::SCOPE_STORE
        );
        if (2 == $displayMode) {
            return '';
        }
        return parent::_toHtml();
    }

    /**
     * Retrieve identities
     * git add
     * @return array
     */
    public function getIdentities(): array
    {
        $displayMode = $this->_scopeConfig->getValue(
            Config::XML_PATH_INDEX_PAGE_DISPLAY_MODE,
            Config::SCOPE_STORE
        );
        if (2 == $displayMode) {
            return [];
        }
        return parent::getIdentities();
    }
}

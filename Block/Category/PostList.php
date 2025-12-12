<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Category;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Api\Data\BlogCategoryInterface;
use MageOS\Blog\Block\Post\PostList\Toolbar;
use MageOS\Blog\Model\Config\Source\CategoryDisplayMode;
use MageOS\Blog\Model\Config\Source\PostsSortBy;
use MageOS\Blog\Block\Post\PostList as PostListBlock;
class PostList extends PostListBlock
{

    /**
     * Prepare posts collection
     *
     * @return void
     */
    protected function _preparePostCollection(): void
    {
        parent::_preparePostCollection();
        if ($category = $this->getCategory()) {
            $this->_postCollection->addCategoryFilter($category);
        }
    }

    /**
     * Retrieve collection order field
     *
     * @return string
     */
    public function getCollectionOrderField(): string
    {
        $postsSortBy = $this->getCategory()->getData('posts_sort_by');

        return match ($postsSortBy) {
            PostsSortBy::POSITION => self::POSTS_SORT_FIELD_BY_POSITION,
            PostsSortBy::TITLE => self::POSTS_SORT_FIELD_BY_TITLE,
            default => parent::getCollectionOrderField(),
        };
    }

    /**
     * Retrieve collection order direction
     *
     * @return string
     */
    public function getCollectionOrderDirection(): string
    {
        $postsSortBy = $this->getCategory()->getData('posts_sort_by');
        if (PostsSortBy::TITLE == $postsSortBy) {
            return SortOrder::SORT_ASC;
        }
        return parent::getCollectionOrderDirection();
    }

    /**
     * Retrieve category instance
     *
     * @return BlogCategoryInterface
     */
    public function getCategory(): BlogCategoryInterface
    {
        return $this->_coreRegistry->registry('current_blog_category');
    }

    /**
     * Retrieve true when display of this block is allowed
     *
     * @return bool
     */
    protected function canDisplay(): bool
    {
        $displayMode = $this->getCategory()->getData('display_mode');
        return ($displayMode == CategoryDisplayMode::POSTS);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->canDisplay()) {
            return '';
        }

        return parent::_toHtml();
    }

    /**
     * Preparing global layout
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout(): static
    {
        if ($category = $this->getCategory()) {
            $this->_addBreadcrumbs($category);
            $this->pageConfig->addBodyClass('blog-category-' . $category->getIdentifier());
            $this->pageConfig->getTitle()->set($category->getMetaTitle());
            $this->pageConfig->setKeywords($category->getMetaKeywords());
            $this->pageConfig->setDescription($category->getMetaDescription());

            if ($this->config->getDisplayCanonicalTag(\MageOS\Blog\Model\Config::CANONICAL_PAGE_TYPE_CATEGORY)) {

                $layoutUpdate = $category->getData('layout_update_xml') ?: '';
                if (false === strpos($layoutUpdate, 'rel="canonical"')) {
                    $canonicalUrl = $category->getCanonicalUrl();
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
            }

            $robots = $category->getData('meta_robots');
            if ($robots) {
                $this->pageConfig->setRobots($robots);
            }

            $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle) {
                $pageMainTitle->setPageTitle(
                    $this->escapeHtml($category->getTitle())
                );
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * Prepare breadcrumbs
     *
     * @param  string $title
     * @param  string $key
     * @throws LocalizedException
     * @return void
     */
    protected function _addBreadcrumbs($title = null, $key = null): void
    {
        parent::_addBreadcrumbs();
        if ($breadcrumbsBlock = $this->getBreadcrumbsBlock()) {
            $category = $this->getCategory();
            $parentCategories = [];
            while ($parentCategory = $category->getParentCategory()) {
                if (isset($parentCategories[$parentCategory->getId()])) {
                    break;
                }
                $parentCategories[$parentCategory->getId()] = $category = $parentCategory;
            }
            $parentCategories = array_values($parentCategories);

            for ($i = count($parentCategories) - 1; $i >= 0; $i--) {
                $category = $parentCategories[$i];
                $breadcrumbsBlock->addCrumb('blog_parent_category_' . $category->getId(), [
                    'label' => $category->getTitle(),
                    'title' => $category->getTitle(),
                    'link'  => $category->getCategoryUrl()
                ]);
            }

            $category = $this->getCategory();
            $breadcrumbsBlock->addCrumb('blog_category', [
                'label' => $category->getTitle(),
                'title' => $category->getTitle()
            ]);
        }
    }

    /**
     * Retrieve identities
     *
     * @return array
     */
    public function getIdentities(): array
    {
        if ($this->canDisplay()) {
            return parent::getIdentities();
        }

        return [];
    }

    /**
     * Get template type
     *
     * @return string
     */
    public function getPostTemplateType(): string
    {
        $template = (string)$this->getCategory()->getData('posts_list_template');
        if ($template) {
            return $template;
        }

        return parent::getPostTemplateType();
    }

    /**
     * Retrieve Toolbar Block
     * @return Toolbar
     */
    public function getToolbarBlock(): Toolbar
    {
        $toolBarBlock = parent::getToolbarBlock();
        $limit = (int)$this->getCategory()->getData('posts_per_page');

        if ($limit) {
            $toolBarBlock->setData('limit', $limit);
        }

        return $toolBarBlock;
    }
}

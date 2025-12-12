<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post;

use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Block\Post\PostList\AbstractList;
use MageOS\Blog\Block\Post\PostList\Toolbar;
use MageOS\Blog\Model\Config;

class PostList extends AbstractList
{
    protected string $_defaultToolbarBlock = Toolbar::class;
    protected $toolbarBlock;

    /**
     * Preparing global layout
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout(): static
    {
        $page = (int)$this->_request->getParam($this->getPageParamName());

        if ($page > 1) {
            //$this->pageConfig->setRobots('NOINDEX,FOLLOW');
            $prefix = (__('Page') . ' ' . $page) . ' - ';
            $this->pageConfig->getTitle()->set(
                $prefix . $this->pageConfig->getTitle()->getShortHeading()
            );
            if ($description = $this->pageConfig->getDescription()) {
                $this->pageConfig->setDescription($prefix . $description);
            }

            $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
            if ($pageMainTitle) {
                $pageMainTitle->setPageTitle(
                    $prefix . $pageMainTitle->getPageTitle()
                );
            }
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve post html
     * @param  \MageOS\Blog\Model\Post $post
     * @return string
     */
    public function getPostHtml($post)
    {
        return $this->getChildBlock('blog.posts.list.item')->setPost($post)->toHtml();
    }

    /**
     * Get template type
     * @deprecated
     * @return string
     */
    /*protected function getPostTemplateType(): string
    {
        return (string)$this->_scopeConfig->getValue(
            Config::XML_PATH_LIST_TEMPLATE,
            Config::SCOPE_STORE
        );
    }*/

    /**
     * Retrieve Toolbar Block
     * @return Toolbar
     * @throws LocalizedException
     */
    public function getToolbarBlock()
    {
        if (null === $this->toolbarBlock) {
            $blockName = $this->getToolbarBlockName();

            if ($blockName) {
                $block = $this->getLayout()->getBlock($blockName);
                if ($block) {
                    $this->toolbarBlock = $block;
                }
            }
            if (!$this->toolbarBlock) {
                $this->toolbarBlock = $this->getLayout()->createBlock($this->_defaultToolbarBlock, uniqid(microtime()));
            }
        }

        return $this->toolbarBlock;
    }

    /**
     * Retrieve Toolbar Html
     * @return string
     */
    public function getToolbarHtml()
    {
        return $this->getChildHtml('toolbar');
    }

    /**
     * Before block to html
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->getPostCollection();

        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);
        $this->setChild('toolbar', $toolbar);

        return parent::_beforeToHtml();
    }

    /**
     * Prepare breadcrumbs
     *
     * @param  string $title
     * @param  string $key
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    protected function _addBreadcrumbs($title = null, $key = null)
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
                    'link' => $this->_url->getBaseUrl(),
                ]
            );

            if ($title) {
                $breadcrumbsBlock->addCrumb($key ?: 'blog_item', ['label' => $title, 'title' => $title]);
            }
        }
    }

    /**
     * Retrieve breadcrumbs block
     *
     * @return mixed
     */
    protected function getBreadcrumbsBlock()
    {
        return $this->getLayout()->getBlock('breadcrumbs');
    }
}

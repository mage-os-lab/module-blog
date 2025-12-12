<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Search;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Model\Config;

/**
 * Blog search result block
 */
class PostList extends \MageOS\Blog\Block\Post\PostList
{
    /**
     * Retrieve query
     * @return string
     */
    public function getQuery()
    {
        return urldecode((string)$this->getRequest()->getParam('q'));
    }

    /**
     * Prepare posts collection
     *
     * @return void
     * @throws NoSuchEntityException
     */
    protected function _preparePostCollection(): void
    {
        parent::_preparePostCollection();
        $this->_postCollection->addSearchFilter(
            $this->getQuery()
        );
        $this->_postCollection->setOrder(
            self::POSTS_SORT_FIELD_BY_PUBLISH_TIME,
            \Magento\Framework\Api\SortOrder::SORT_DESC
        );
    }

    /**
     * Retrieve collection order field
     *
     * @return string
     */
    public function getCollectionOrderField(): string
    {
        return 'search_rate';
    }

    /**
     * Preparing global layout
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareLayout(): static
    {
        $title = $this->_getTitle();
        $this->_addBreadcrumbs($title, 'blog_search');
        $this->pageConfig->getTitle()->set($title);
        /*
        $page = $this->_request->getParam($this->getPageParamName());
        if ($page < 2) {
        */
            $robots = $this->config->getSearchRobots();
            $this->pageConfig->setRobots($robots);
        /*
        }

        if ($page > 1) {
            $this->pageConfig->setRobots('NOINDEX,FOLLOW');
        }
        */
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $pageMainTitle->setPageTitle(
                $this->escapeHtml($title)
            );
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve title
     * @return string
     */
    protected function _getTitle()
    {
        return __('Search "%1"', $this->getQuery());
    }

    /**
     * Get template type
     *
     * @return string
     */
    public function getPostTemplateType(): string
    {
        $template = (string)$this->_scopeConfig->getValue(
            Config::XML_PATH_SEARCH_TEMPLATE,
            Config::SCOPE_STORE
        );

        if ($template) {
            return $template;
        }

        return parent::getPostTemplateType();
    }
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Post\PostList;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template;
use MageOS\Blog\Model\Config;
use Magento\Framework\View\Element\Template\Context;

class Toolbar extends Template
{
    /**
     * @var Config|null
     */
    private $config;
    /**
     * Products collection
     *
     * @var \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
     */
    protected $_collection = null;

    /**
     * Default block template
     * @var string
     */
    protected $_template = 'post/list/toolbar.phtml';
    const string PAGE_PARM_NAME = 'page';

    public function __construct(
        Context $context,
        array $data = [],
        Config $config = null
    ) {
        parent::__construct($context, $data);
        $objectManager = ObjectManager::getInstance();
        $this->config = $config ?: $objectManager->create(Config::class);
    }

    /**
     * Set collection to pager
     *
     * @param \Magento\Framework\Data\Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
        if ($this->getCurrentOrder()) {
            $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
        }
        return $this;
    }

    /**
     * Return products collection instance
     *
     * @return \Magento\Framework\Model\Resource\Db\Collection\AbstractCollection
     */
    public function getCollection()
    {
        return $this->_collection;
    }

    /**
     * Get specified posts limit display per page
     *
     * @return string
     */
    public function getLimit(): string
    {
        return $this->getData('limit') ?: $this->_scopeConfig->getValue(
            Config::XML_PATH_LIST_POSTS_PER_PAGE,
            Config::SCOPE_STORE
        );
    }

    /**
     * Return current page from request
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        $page = (int) $this->_request->getParam($this->getPageParamName());
        return $page ? $page : 1;
    }

    /**
     * @return bool|\Magento\Framework\DataObject|\Magento\Framework\View\Element\AbstractBlock|\Magento\Theme\Block\Html\Pager
     */
    public function getPagerBlock()
    {
        $pagerBlock = $this->getChildBlock('post_list_toolbar_pager');
        if ($pagerBlock instanceof \Magento\Framework\DataObject) {
            /* @var $pagerBlock \Magento\Theme\Block\Html\Pager */

            $pagerBlock->setUseContainer(
                false
            )->setShowPerPage(
                false
            )->setShowAmounts(
                false
            )->setPageVarName(
                $this->getPageParamName()
            )->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setLimit(
                $this->getLimit()
            )->setCollection(
                $this->getCollection()
            );
        } else {
            $pagerBlock = false;
        }


        return $pagerBlock;
    }

    /**
     * Render pagination HTML
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        $pagerBlock = $this->getPagerBlock();
        if ($pagerBlock instanceof \Magento\Framework\DataObject) {
            return $pagerBlock->toHtml();
        }

        return '';
    }

    /**
     * This will help for extending configuration
     * @return string
     */
    public function getPageParamName(): string
    {
        return 'page';
    }
}

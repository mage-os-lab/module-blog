<?php
declare(strict_types=1);

namespace MageOS\Blog\Helper;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\View\Result\PageFactory;
use MageOS\Blog\Model\Config;

/**
 * MageOS Blog Helper
 */
class Page extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected DesignInterface $_design;
    protected TimezoneInterface $_localeDate;
    protected PageFactory $resultPageFactory;
    protected ScopeConfigInterface $_scopeConfig;

    public function __construct(
        Context $context,
        DesignInterface $design,
        TimezoneInterface $localeDate,
        PageFactory $resultPageFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_design = $design;
        $this->_localeDate = $localeDate;
        $this->resultPageFactory = $resultPageFactory;
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Return result blog page
     *
     * @param Action $action
     * @param \Magento\Framework\Model\AbstractModel $page
     * @return \Magento\Framework\View\Result\Page|bool
     */
    public function prepareResultPage(Action $action, $page)
    {
        if ($page->getCustomThemeFrom() && $page->getCustomThemeTo()) {
            $inRange = $this->_localeDate->isScopeDateInInterval(
                null,
                $page->getCustomThemeFrom(),
                $page->getCustomThemeTo()
            );
        } else {
            $inRange = false;
        }

        if ($page->getCustomTheme()) {
            if ($inRange) {
                $this->_design->setDesignTheme($page->getCustomTheme());
            }
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // dispatch event
        $this->_eventManager->dispatch('blog_page_render_before', [
            'action' => $action,
            'page' => $page,
            'result_page' => $resultPage,
        ]);
        $fullActionName = $action->getRequest()->getFullActionName();
        if ($inRange
            && $page->getCustomLayout()
            && $page->getCustomLayout() != 'empty'
        ) {
            $handle = $page->getCustomLayout();
        } else {
            $handle = $page->getPageLayout();
        }
        if ($handle) {
            $resultPage->getConfig()->setPageLayout($handle);
        } else {
            if ('blog_post_view' == $fullActionName) {
                $handle = $this->_scopeConfig->getValue(
                    Config::XML_PATH_POST_LAYOUT,
                    Config::SCOPE_STORE
                );
                $resultPage->getConfig()->setPageLayout($handle);
            } elseif ('blog_index_index' == $fullActionName) {
                $handle = $this->_scopeConfig->getValue(
                    Config::XML_PATH_INDEX_PAGE_LAYOUT,
                    Config::SCOPE_STORE
                );
                $resultPage->getConfig()->setPageLayout($handle);
            } elseif ('blog_tag_view' == $fullActionName) {
                $handle = $this->_scopeConfig->getValue(
                    Config::XML_PATH_TAG_PAGE_LAYOUT,
                    Config::SCOPE_STORE
                );
                $resultPage->getConfig()->setPageLayout($handle);
            } elseif ('blog_category_view' == $fullActionName) {
                $handle = $this->_scopeConfig->getValue(
                    Config::XML_PATH_DESIGN_CATEGORY_PAGE_LAYOUT,
                    Config::SCOPE_STORE
                );
                $resultPage->getConfig()->setPageLayout($handle);
            } elseif ('blog_search_index' == $fullActionName) {
                $handle = $this->_scopeConfig->getValue(
                    Config::XML_PATH_SEARCH_PAGE_LAYOUT,
                    Config::SCOPE_STORE
                );
                $resultPage->getConfig()->setPageLayout($handle);
            }
        }

        $resultPage->addHandle($fullActionName);
        $resultPage->addPageLayoutHandles(['id' => str_replace('/', '_', (string)$page->getIdentifier())]);

        $this->_eventManager->dispatch(
            $fullActionName . '_render',
            ['page' => $page, 'controller_action' => $action]
        );

        if ($inRange && $page->getCustomLayoutUpdateXml()) {
            $layoutUpdate = $page->getCustomLayoutUpdateXml();
        } else {
            $layoutUpdate = $page->getLayoutUpdateXml();
        }
        if ($layoutUpdate) {
            $resultPage->getLayout()->getUpdate()->addUpdate($layoutUpdate);
        }

        return $resultPage;
    }
}

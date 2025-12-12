<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Import;

/**
 * Blog available imports list controller
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * Start available import execute
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('MageOS_Blog::import');
        $title = __('Blog Import');
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_addBreadcrumb($title, $title);
        $this->_view->renderLayout();
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageOS_Blog::import');
    }
}

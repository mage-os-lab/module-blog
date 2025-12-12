<?php
declare(strict_types=1);
namespace MageOS\Blog\Controller\Search;

/**
 * Blog search results view
 */
class Index extends \MageOS\Blog\App\Action\Action
{
    /**
     * View blog search results action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->moduleEnabled()) {
            return $this->_forwardNoroute();
        }

        $resultPage = $this->_objectManager->get(\MageOS\Blog\Helper\Page::class)
            ->prepareResultPage($this, new \Magento\Framework\DataObject());
        return $resultPage;
    }
}

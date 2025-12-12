<?php
declare(strict_types=1);
namespace MageOS\Blog\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

/**
 * Blog home page view
 */
class Index extends \MageOS\Blog\App\Action\Action
{
    /**
     * View blog homepage action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->moduleEnabled()) {
            //return $this->_forwardNoroute();
            return $this->_objectManager->get(ResultFactory::class)
                ->create(ResultFactory::TYPE_FORWARD)
                ->forward('noroute');
        }

        $resultPage = $this->_objectManager->get(\MageOS\Blog\Helper\Page::class)
            ->prepareResultPage($this, new \Magento\Framework\DataObject());
        return $resultPage;
    }
}

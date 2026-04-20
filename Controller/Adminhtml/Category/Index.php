<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::category';

    public function execute(): ResultInterface
    {
        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu('MageOS_Blog::category');
        $result->getConfig()->getTitle()->prepend((string) __('Blog Categories'));
        return $result;
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class NewAction extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function execute(): ResultInterface
    {
        /** @var Forward $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $result->forward('edit');
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use MageOS\Blog\Api\CategoryRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::category';

    public function __construct(
        Context $context,
        private readonly CategoryRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $categoryId = (int) $this->getRequest()->getParam('category_id');

        if ($categoryId > 0) {
            try {
                $category = $this->repository->getById($categoryId);
                $this->registry->register('mageos_blog_category', $category);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage((string) __('This category no longer exists.'));
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/');
            }
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu('MageOS_Blog::category');
        $result->getConfig()->getTitle()->prepend((string) (
            $categoryId > 0 ? __('Edit Category') : __('New Category')
        ));
        return $result;
    }
}

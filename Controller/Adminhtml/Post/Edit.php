<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use MageOS\Blog\Api\PostRepositoryInterface;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function __construct(
        Context $context,
        private readonly PostRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $postId = (int) $this->getRequest()->getParam('post_id');

        if ($postId > 0) {
            try {
                $post = $this->repository->getById($postId);
                $this->registry->register('mageos_blog_post', $post);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage((string) __('This post no longer exists.'));
                /** @var Redirect $redirect */
                $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $redirect->setPath('*/*/');
            }
        }

        /** @var Page $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $result->setActiveMenu('MageOS_Blog::post');
        $result->getConfig()->getTitle()->prepend((string) (
            $postId > 0 ? __('Edit Post') : __('New Post')
        ));
        return $result;
    }
}

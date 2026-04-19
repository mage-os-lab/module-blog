<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\PostRepositoryInterface;

class Delete extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function __construct(
        Context $context,
        private readonly PostRepositoryInterface $repository
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $postId = (int) $this->getRequest()->getParam('post_id');

        if ($postId <= 0) {
            $this->messageManager->addErrorMessage((string) __('Post id is required.'));
            return $result->setPath('*/*/');
        }

        try {
            $this->repository->deleteById($postId);
            $this->messageManager->addSuccessMessage((string) __('Post deleted.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('Post not found.'));
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not delete post: %1', $e->getMessage()));
            return $result->setPath('*/*/edit', ['post_id' => $postId]);
        }

        return $result->setPath('*/*/');
    }
}

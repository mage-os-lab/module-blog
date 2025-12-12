<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

/**
 * Blog post preview controller
 */
class Preview extends \MageOS\Blog\Controller\Adminhtml\Post
{
    public function execute()
    {
        try {
            $post = $this->_getModel();
            if (!$post->getId()) {
                throw new \Exception("Item is not longer exist.", 1);
            }

            $previewUrl = $this->_objectManager->get(\MageOS\Blog\Model\PreviewUrl::class);
            $redirectUrl = $previewUrl->getUrl(
                $post,
                $previewUrl::CONTROLLER_POST
            );

            $this->getResponse()->setRedirect($redirectUrl);
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong %1', $e->getMessage())
            );
            $this->_redirect('*/*/edit', [$this->_idKey => $post->getId()]);
        }
    }
}

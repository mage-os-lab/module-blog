<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Post;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\StoreVisibility;

class View implements HttpGetActionInterface
{
    public const REGISTRY_KEY = 'current_blog_post';

    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly PostRepositoryInterface $repository,
        private readonly Registry $registry,
        private readonly StoreManagerInterface $storeManager,
        private readonly StoreVisibility $storeVisibility,
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->notFound();
        }

        $postId = (int) $this->request->getParam('id');
        if ($postId <= 0) {
            return $this->notFound();
        }

        try {
            $post = $this->repository->getById($postId);
        } catch (NoSuchEntityException) {
            return $this->notFound();
        }

        if ($post->getStatus() !== BlogPostStatus::Published->value) {
            return $this->notFound();
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->storeVisibility->isVisible($post->getStoreIds(), $storeId)) {
            return $this->notFound();
        }

        $this->registry->register(self::REGISTRY_KEY, $post);

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set((string) ($post->getMetaTitle() ?: $post->getTitle()));
        return $page;
    }

    private function notFound(): Forward
    {
        /** @var Forward $forward */
        $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $forward->forward('noroute');
    }
}

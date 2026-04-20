<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Category;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Model\Config;

class View implements HttpGetActionInterface
{
    public const REGISTRY_KEY = 'current_blog_category';

    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly CategoryRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->notFound();
        }

        $categoryId = (int) $this->request->getParam('id');
        if ($categoryId <= 0) {
            return $this->notFound();
        }

        try {
            $category = $this->repository->getById($categoryId);
        } catch (NoSuchEntityException) {
            return $this->notFound();
        }

        if (!$category->getIsActive()) {
            return $this->notFound();
        }

        $this->registry->register(self::REGISTRY_KEY, $category);

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set((string) ($category->getMetaTitle() ?: $category->getTitle()));
        return $page;
    }

    private function notFound(): Forward
    {
        /** @var Forward $forward */
        $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $forward->forward('noroute');
    }
}

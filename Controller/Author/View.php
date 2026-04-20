<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Author;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Config;

class View implements HttpGetActionInterface
{
    public const REGISTRY_KEY = 'current_blog_author';

    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly AuthorRepositoryInterface $repository,
        private readonly Registry $registry
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->notFound();
        }

        $authorId = (int) $this->request->getParam('id');
        if ($authorId <= 0) {
            return $this->notFound();
        }

        try {
            $author = $this->repository->getById($authorId);
        } catch (NoSuchEntityException) {
            return $this->notFound();
        }

        if (!$author->getIsActive()) {
            return $this->notFound();
        }

        $this->registry->register(self::REGISTRY_KEY, $author);

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set((string) $author->getName());
        return $page;
    }

    private function notFound(): Forward
    {
        /** @var Forward $forward */
        $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $forward->forward('noroute');
    }
}

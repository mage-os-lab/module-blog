<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Search;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use MageOS\Blog\Model\Config;

class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly Config $config
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            /** @var Forward $forward */
            $forward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $forward->forward('noroute');
        }

        // An array-valued q[] must not reach a (string) cast: Magento's error handler turns the
        // resulting "Array to string conversion" warning into an HTTP 500.
        $rawQuery = $this->request->getParam('q', '');
        $query = \is_string($rawQuery) ? trim($rawQuery) : '';
        if ($query === '') {
            /** @var Redirect $redirect */
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $redirect->setPath('blog');
        }

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set((string) __('Blog search — %1', $query));
        return $page;
    }
}

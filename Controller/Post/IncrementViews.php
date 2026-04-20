<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Post;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Session\Generic as GenericSession;

class IncrementViews implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const SESSION_KEY = 'blog_views_counted';

    public function __construct(
        private readonly ResultFactory $resultFactory,
        private readonly RequestInterface $request,
        private readonly ResourceConnection $resource,
        private readonly GenericSession $session
    ) {
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute(): ResultInterface
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $postId = (int) $this->request->getParam('post_id');
        if ($postId <= 0) {
            return $result->setData(['success' => false]);
        }

        $counted = (array) ($this->session->getData(self::SESSION_KEY) ?? []);
        if (\in_array($postId, $counted, true)) {
            return $result->setData(['success' => true, 'already_counted' => true]);
        }

        $connection = $this->resource->getConnection();
        $connection->update(
            $this->resource->getTableName('mageos_blog_post'),
            ['views_count' => new Expression('views_count + 1')],
            ['post_id = ?' => $postId]
        );

        $counted[] = $postId;
        $this->session->setData(self::SESSION_KEY, $counted);

        return $result->setData(['success' => true]);
    }
}

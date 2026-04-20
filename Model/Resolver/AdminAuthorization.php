<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class AdminAuthorization
{
    public function __construct(
        private readonly AuthorizationInterface $authorization,
    ) {
    }

    public function assertAuthorized(ContextInterface $context, string $resource): void
    {
        $isAdmin = false;

        if (method_exists($context, 'getExtensionAttributes')) {
            $extensionAttributes = $context->getExtensionAttributes();
            if (
                \is_object($extensionAttributes)
                && method_exists($extensionAttributes, 'getIsAdmin')
            ) {
                $isAdmin = (bool) $extensionAttributes->getIsAdmin();
            }
        }

        if (!$isAdmin) {
            throw new GraphQlAuthorizationException(__('Admin authentication required.'));
        }

        if (!$this->authorization->isAllowed($resource)) {
            throw new GraphQlAuthorizationException(__('Not authorized to access %1.', $resource));
        }
    }
}

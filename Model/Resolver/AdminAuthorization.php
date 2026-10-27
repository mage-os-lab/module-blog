<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class AdminAuthorization
{
    public function __construct(
        private readonly AuthorizationInterface $authorization,
    ) {
    }

    /**
     * Whether the caller holds an admin token whose role grants the ACL resource.
     */
    public function isAuthorized(ContextInterface $context, string $resource): bool
    {
        return $this->isAdmin($context) && $this->authorization->isAllowed($resource);
    }

    public function assertAuthorized(ContextInterface $context, string $resource): void
    {
        if (!$this->isAdmin($context)) {
            throw new GraphQlAuthorizationException(__('Admin authentication required.'));
        }

        if (!$this->authorization->isAllowed($resource)) {
            throw new GraphQlAuthorizationException(__('Not authorized to access %1.', $resource));
        }
    }

    /**
     * Core's GraphQL context reports the authenticated caller's type; the extension attribute is
     * kept for contexts that carry the flag instead.
     */
    private function isAdmin(ContextInterface $context): bool
    {
        if (method_exists($context, 'getUserType')
            && $context->getUserType() === UserContextInterface::USER_TYPE_ADMIN
        ) {
            return true;
        }

        if (!method_exists($context, 'getExtensionAttributes')) {
            return false;
        }

        $extensionAttributes = $context->getExtensionAttributes();

        return \is_object($extensionAttributes)
            && method_exists($extensionAttributes, 'getIsAdmin')
            && (bool) $extensionAttributes->getIsAdmin();
    }
}

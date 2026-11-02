<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * An author's email is for the admin side: visitors get null, whoever the author is.
 */
class EmailResolver implements ResolverInterface
{
    public function __construct(
        private readonly ReadPolicy $readPolicy,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?string {
        $email = $value['email'] ?? null;

        if (!\is_string($email) || !$this->readPolicy->canSeeAuthorEmail($context)) {
            return null;
        }

        return $email;
    }
}

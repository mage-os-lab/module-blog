<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * RoutableInterface makes redirect_code non-null, but only the route query knows it. Everywhere
 * else a blog entity is not behind a redirect, so the answer is 0.
 */
class RedirectCodeResolver implements ResolverInterface
{
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
    ): int {
        return (int) ($value['redirect_code'] ?? 0);
    }
}

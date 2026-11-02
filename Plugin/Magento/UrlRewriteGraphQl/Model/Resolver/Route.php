<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\Route as CoreRoute;
use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;

/**
 * A blog URL and an unknown URL must answer the route query the same way, otherwise the difference
 * tells a visitor which blog URLs exist, drafts included.
 */
class Route
{
    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     *
     * @return array<string, mixed>|null
     */
    public function aroundResolve(
        CoreRoute $subject,
        callable $proceed,
        Field $field,
        mixed $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?array {
        try {
            return $proceed($field, $context, $info, $value, $args);
        } catch (RouteNotSupportedException) {
            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\Route as CoreRoute;
use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;
use MageOS\Blog\Model\Resolver\UrlResolver\UrlEntityReadability;

/**
 * A blog URL the caller may not read and an unknown URL must answer the route query the same way (null),
 * otherwise the difference tells a visitor which blog URLs exist, drafts included.
 *
 * Core's route query loads the entity through BlogEntityDataProvider, which cannot see the GraphQL context.
 * So the check happens here, on the way out: anything ReadPolicy denies is dropped and null returned, and
 * an entity the provider cannot load (RouteNotSupportedException) ends in the same null.
 */
class Route
{
    public function __construct(
        private readonly UrlEntityReadability $readability,
    ) {
    }

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
            $result = $proceed($field, $context, $info, $value, $args);
        } catch (RouteNotSupportedException) {
            return null;
        }

        if ($result === null || !isset($result['type'])) {
            return $result;
        }

        $type = (string) $result['type'];
        if (!str_starts_with(strtolower($type), 'blog_')) {
            return $result;
        }

        return $this->readability->canRead($type, (int) ($result['id'] ?? 0), $context) ? $result : null;
    }
}

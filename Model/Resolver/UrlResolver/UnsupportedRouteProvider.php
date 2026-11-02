<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\DataProvider\EntityDataProviderInterface;

/**
 * Core's route query looks up one data provider per entity type and dereferences it blindly, so a
 * blog URL used to crash it. Until the blog types implement RoutableInterface this provider makes
 * that a clean "not supported", which the Route plugin turns into the same null an unknown URL gets.
 */
class UnsupportedRouteProvider implements EntityDataProviderInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getData(
        string $entityType,
        int $id,
        ?ResolveInfo $info = null,
        ?int $storeId = null,
    ): array {
        throw new RouteNotSupportedException($entityType);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

/**
 * Thrown by UnsupportedRouteProvider and turned into an empty route result by the Route plugin.
 */
class RouteNotSupportedException extends \RuntimeException
{
    public function __construct(string $entityType)
    {
        parent::__construct(\sprintf('The route query does not support the entity type %s.', $entityType));
    }
}

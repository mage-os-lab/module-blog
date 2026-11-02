<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

/**
 * Thrown by BlogEntityDataProvider when it cannot supply an entity, and turned into an empty route result
 * (the same null an unknown URL gets) by the Route plugin.
 */
class RouteNotSupportedException extends \RuntimeException
{
    public function __construct(string $entityType)
    {
        parent::__construct(\sprintf('The route query does not support the entity type %s.', $entityType));
    }
}

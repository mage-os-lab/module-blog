<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\UrlResolver;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;
use MageOS\Blog\Model\Url\UrlRewriteBuilder;

/**
 * Maps the url_rewrite entity type of a route result to the concrete GraphQL type, for
 * RoutableInterface. Returns '' for other modules' types so their resolvers get their turn.
 */
class RoutableTypeResolver implements TypeResolverInterface
{
    private const TYPES = [
        UrlRewriteBuilder::ENTITY_TYPE_POST => 'BlogPost',
        UrlRewriteBuilder::ENTITY_TYPE_CATEGORY => 'BlogCategory',
        UrlRewriteBuilder::ENTITY_TYPE_TAG => 'BlogTag',
        UrlRewriteBuilder::ENTITY_TYPE_AUTHOR => 'BlogAuthor',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function resolveType(array $data): string
    {
        return self::TYPES[strtolower((string) ($data['type'] ?? ''))] ?? '';
    }
}

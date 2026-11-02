<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl as CoreEntityUrl;
use MageOS\Blog\Model\Resolver\UrlResolver\UrlEntityReadability;

/**
 * Core's urlResolver answers from url_rewrite rows, and the blog writes those for every entity
 * whatever its status. Without this, a guessed draft slug returns its id and type to anyone.
 * A blog entity the caller may not read answers exactly like an unknown URL: null.
 */
class EntityUrl
{
    public function __construct(
        private readonly UrlEntityReadability $readability,
    ) {
    }

    /**
     * @param array<string, mixed>|null $result
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     *
     * @return array<string, mixed>|null
     */
    public function afterResolve(
        CoreEntityUrl $subject,
        ?array $result,
        Field $field,
        mixed $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?array {
        if ($result === null || empty($result['id']) || !isset($result['type'])) {
            return $result;
        }

        return $this->readability->canRead((string) $result['type'], (int) $result['id'], $context)
            ? $result
            : null;
    }
}

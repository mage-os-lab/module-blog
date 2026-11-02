<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * BlogCategory.parent_id (deprecated, use parent): the id only when the caller may read the parent.
 *
 * A top-level category keeps its stored value (0 or null). A hidden or deleted parent resolves to
 * null, so a visitor cannot tell it from a missing one.
 */
class ParentIdResolver implements ResolverInterface
{
    public function __construct(
        private readonly NestedEntityLoader $loader,
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
    ): ?int {
        $parentId = isset($value['parent_id']) ? (int) $value['parent_id'] : null;
        if ($parentId === null || $parentId === 0) {
            return $parentId;
        }

        $parent = $this->loader->category($parentId);

        return $parent !== null && $this->readPolicy->canReadCategory($context, $parent) ? $parentId : null;
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\Post\NestedEntityLoader;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * BlogCategory.parent: the parent category, or null for a top-level category and for a parent the
 * caller may not read.
 */
class ParentResolver implements ResolverInterface
{
    public function __construct(
        private readonly NestedEntityLoader $loader,
        private readonly CategoryMapper $categoryMapper,
        private readonly ReadPolicy $readPolicy,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?array {
        $parentId = isset($value['parent_id']) ? (int) $value['parent_id'] : 0;
        if ($parentId === 0) {
            return null;
        }

        $parent = $this->loader->category($parentId);
        if ($parent === null || !$this->readPolicy->canReadCategory($context, $parent)) {
            return null;
        }

        return $this->categoryMapper->toArray($parent);
    }
}

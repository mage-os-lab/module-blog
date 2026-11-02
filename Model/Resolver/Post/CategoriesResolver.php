<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;

class CategoriesResolver implements ResolverInterface
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
     * @return list<array<string, mixed>>
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): array {
        $ids = isset($value['category_ids']) && \is_array($value['category_ids']) ? $value['category_ids'] : [];
        $result = [];
        foreach ($ids as $id) {
            $category = $this->loader->category((int) $id);
            if ($category === null || !$this->readPolicy->canReadCategory($context, $category)) {
                continue;
            }
            $result[] = $this->categoryMapper->toArray($category);
        }

        return $result;
    }
}

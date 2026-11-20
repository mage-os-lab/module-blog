<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * BlogPost.category_ids (deprecated, use categories): only ids of categories the caller may read.
 *
 * A hidden or deleted category must not be named to a visitor, so it is left out like it does not exist.
 */
class CategoryIdsResolver implements ResolverInterface
{
    public function __construct(
        private readonly NestedEntityLoader $loader,
        private readonly ReadPolicy $readPolicy,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return list<int>
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
            if ($category !== null && $this->readPolicy->canReadCategory($context, $category)) {
                $result[] = (int) $id;
            }
        }

        return $result;
    }
}

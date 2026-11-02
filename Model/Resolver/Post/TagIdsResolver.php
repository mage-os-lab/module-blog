<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * BlogPost.tag_ids (deprecated, use tags): only ids of tags the caller may read.
 *
 * A hidden or deleted tag must not be named to a visitor, so it is left out like it does not exist.
 */
class TagIdsResolver implements ResolverInterface
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
        $ids = isset($value['tag_ids']) && \is_array($value['tag_ids']) ? $value['tag_ids'] : [];
        $result = [];
        foreach ($ids as $id) {
            $tag = $this->loader->tag((int) $id);
            if ($tag !== null && $this->readPolicy->canReadTag($context, $tag)) {
                $result[] = (int) $id;
            }
        }

        return $result;
    }
}

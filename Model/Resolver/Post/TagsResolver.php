<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;

class TagsResolver implements ResolverInterface
{
    public function __construct(
        private readonly NestedEntityLoader $loader,
        private readonly TagMapper $tagMapper,
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
        $ids = isset($value['tag_ids']) && \is_array($value['tag_ids']) ? $value['tag_ids'] : [];
        $result = [];
        foreach ($ids as $id) {
            $tag = $this->loader->tag((int) $id);
            if ($tag === null || !$this->readPolicy->canReadTag($context, $tag)) {
                continue;
            }
            $result[] = $this->tagMapper->toArray($tag);
        }

        return $result;
    }
}

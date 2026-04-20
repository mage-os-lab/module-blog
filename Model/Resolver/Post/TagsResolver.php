<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;

class TagsResolver implements ResolverInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly TagMapper $tagMapper,
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
            try {
                $tag = $this->tagRepository->getById((int) $id);
            } catch (NoSuchEntityException) {
                continue;
            }
            $result[] = $this->tagMapper->toArray($tag);
        }

        return $result;
    }
}

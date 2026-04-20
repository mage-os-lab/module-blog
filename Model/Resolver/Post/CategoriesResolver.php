<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;

class CategoriesResolver implements ResolverInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryMapper $categoryMapper,
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
            try {
                $category = $this->categoryRepository->getById((int) $id);
            } catch (NoSuchEntityException) {
                continue;
            }
            $result[] = $this->categoryMapper->toArray($category);
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterKeyNormalizer;

class ListResolver implements ResolverInterface
{
    private const FILTER_FIELD_MAP = [
        'id' => 'category_id',
        'title' => 'title',
        'url_key' => 'url_key',
        'parent_id' => 'parent_id',
        'is_active' => 'is_active',
        'include_in_menu' => 'include_in_menu',
        'store_id' => CategoryInterface::STORE_IDS,
    ];

    /** Unsuffixed filter key => the legacy `*_id` key it is the same filter as. */
    private const FILTER_KEY_ALIASES = [
        'store' => 'store_id',
        'parent' => 'parent_id',
    ];

    /** Filter keys that name another blog entity the caller may not be allowed to read. */
    private const RELATION_FILTERS = [
        'parent_id' => RelationFilterGuard::KIND_CATEGORY,
    ];

    private const SORT_FIELD_MAP = [
        'title' => 'title',
        'position' => 'position',
        'id' => 'category_id',
    ];

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly FilterBuilder $filterBuilder,
        private readonly CategoryMapper $categoryMapper,
        private readonly ReadPolicy $readPolicy,
        private readonly FilterKeyNormalizer $filterKeyNormalizer,
        private readonly RelationFilterGuard $relationFilterGuard,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): array {
        $args ??= [];
        $filter = isset($args['filter']) && \is_array($args['filter']) ? $args['filter'] : null;
        if ($filter !== null) {
            $filter = $this->filterKeyNormalizer->normalize($filter, self::FILTER_KEY_ALIASES);
            $filter = $this->relationFilterGuard->restrict($context, $filter, self::RELATION_FILTERS);
        }
        $sort = isset($args['sort']) && \is_array($args['sort']) ? $args['sort'] : null;
        $pageSize = isset($args['pageSize']) ? (int) $args['pageSize'] : 20;
        $currentPage = isset($args['currentPage']) ? (int) $args['currentPage'] : 1;

        $searchCriteria = $this->filterBuilder->build(
            $filter,
            $sort,
            $pageSize,
            $currentPage,
            self::FILTER_FIELD_MAP + self::SORT_FIELD_MAP,
            $this->readPolicy->categoryListFilters($context),
        );

        $results = $this->categoryRepository->getList($searchCriteria);
        $total = $results->getTotalCount();

        $items = array_map(
            fn ($cat) => $this->categoryMapper->toArray($cat),
            $results->getItems(),
        );

        return [
            'items' => $items,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => $pageSize > 0 ? (int) ceil($total / $pageSize) : 0,
            ],
            'total_count' => $total,
        ];
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Model\Resolver\Hydrator\LinkHydrator;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\SearchCriteria\PageArguments;

class ListResolver implements ResolverInterface
{
    private const FILTER_FIELD_MAP = [
        'id' => 'category_id',
        'title' => 'title',
        'url_key' => 'url_key',
        'parent_id' => 'parent_id',
        'is_active' => 'is_active',
        'include_in_menu' => 'include_in_menu',
        'store_id' => 'store_id',
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
        private readonly PageArguments $pageArguments,
        private readonly LinkHydrator $linkHydrator,
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
        $sort = isset($args['sort']) && \is_array($args['sort']) ? $args['sort'] : null;
        ['pageSize' => $pageSize, 'currentPage' => $currentPage] = $this->pageArguments->normalize($args);

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

        // After the read policy filtered the page, so links are only loaded for what the caller may see.
        $entities = $results->getItems();
        $this->linkHydrator->hydrateCategories($entities);

        $items = array_map(
            fn ($entity) => $this->categoryMapper->toArray($entity),
            $entities,
        );

        return [
            'items' => $items,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'total_pages' => (int) ceil($total / $pageSize),
            ],
            'total_count' => $total,
        ];
    }
}

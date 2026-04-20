<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;

class ListResolver implements ResolverInterface
{
    private const FILTER_FIELD_MAP = [
        'id' => 'author_id',
        'name' => 'name',
        'slug' => 'slug',
        'is_active' => 'is_active',
    ];

    private const SORT_FIELD_MAP = [
        'name' => 'name',
        'id' => 'author_id',
    ];

    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly FilterBuilder $filterBuilder,
        private readonly AuthorMapper $authorMapper,
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
        $pageSize = isset($args['pageSize']) ? (int) $args['pageSize'] : 20;
        $currentPage = isset($args['currentPage']) ? (int) $args['currentPage'] : 1;

        $searchCriteria = $this->filterBuilder->build(
            $filter,
            $sort,
            $pageSize,
            $currentPage,
            self::FILTER_FIELD_MAP + self::SORT_FIELD_MAP,
        );

        $results = $this->authorRepository->getList($searchCriteria);
        $total = $results->getTotalCount();

        $items = array_map(
            fn ($author) => $this->authorMapper->toArray($author),
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

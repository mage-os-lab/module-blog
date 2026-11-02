<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Tag;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Hydrator\LinkHydrator;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterBuilder;
use MageOS\Blog\Model\Resolver\SearchCriteria\PageArguments;

class ListResolver implements ResolverInterface
{
    private const FILTER_FIELD_MAP = [
        'id' => 'tag_id',
        'title' => 'title',
        'url_key' => 'url_key',
        'is_active' => 'is_active',
        'store_id' => 'store_id',
    ];

    private const SORT_FIELD_MAP = [
        'title' => 'title',
        'id' => 'tag_id',
    ];

    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly FilterBuilder $filterBuilder,
        private readonly TagMapper $tagMapper,
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
            $this->readPolicy->tagListFilters($context),
        );

        $results = $this->tagRepository->getList($searchCriteria);
        $total = $results->getTotalCount();

        // After the read policy filtered the page, so links are only loaded for what the caller may see.
        $entities = $results->getItems();
        $this->linkHydrator->hydrateTags($entities);

        $items = array_map(
            fn ($entity) => $this->tagMapper->toArray($entity),
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

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\SearchCriteria;

use Magento\Framework\Api\FilterBuilder as ApiFilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;

/**
 * Builds a SearchCriteria from a GraphQL filter/sort/pagination argument set.
 *
 * Note: the brief called for FilterGroupBuilder to be injected as well, but
 * SearchCriteriaBuilder::addFilters() already wraps a filter list into a group
 * via an internal FilterGroupBuilder, so the extra dependency would be unused.
 */
class FilterBuilder
{
    public function __construct(
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly ApiFilterBuilder $filterBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
    ) {
    }

    /**
     * @param array<string, mixed>|null $filter
     * @param array<string, string>|null $sort
     * @param array<string, string> $fieldMap
     */
    public function build(
        ?array $filter,
        ?array $sort,
        int $pageSize,
        int $currentPage,
        array $fieldMap,
    ): SearchCriteriaInterface {
        if ($filter !== null) {
            foreach ($filter as $graphqlField => $spec) {
                if (!isset($fieldMap[$graphqlField])) {
                    // unknown field — ignore
                    continue;
                }
                if (!\is_array($spec)) {
                    continue;
                }
                $filters = $this->buildFiltersForSpec($fieldMap[$graphqlField], $spec);
                if ($filters !== []) {
                    $this->searchCriteriaBuilder->addFilters($filters);
                }
            }
        }

        if ($sort !== null) {
            foreach ($sort as $graphqlField => $direction) {
                if (!isset($fieldMap[$graphqlField])) {
                    // unknown field — ignore
                    continue;
                }
                $sortOrder = $this->sortOrderBuilder
                    ->setField($fieldMap[$graphqlField])
                    ->setDirection($this->normalizeDirection((string) $direction))
                    ->create();
                $this->searchCriteriaBuilder->addSortOrder($sortOrder);
            }
        }

        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $this->searchCriteriaBuilder->setCurrentPage($currentPage);

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * @param array<string, mixed> $spec
     * @return list<\Magento\Framework\Api\Filter>
     */
    private function buildFiltersForSpec(string $repositoryField, array $spec): array
    {
        $filters = [];

        if (\array_key_exists('eq', $spec) && $spec['eq'] !== null) {
            $filters[] = $this->filterBuilder
                ->setField($repositoryField)
                ->setConditionType('eq')
                ->setValue($spec['eq'])
                ->create();
        }

        if (\array_key_exists('in', $spec) && \is_array($spec['in'])) {
            $filters[] = $this->filterBuilder
                ->setField($repositoryField)
                ->setConditionType('in')
                ->setValue($spec['in'])
                ->create();
        }

        if (\array_key_exists('match', $spec) && $spec['match'] !== null) {
            // TODO: honor match_type (FULL/PARTIAL) in v1.1 — v1 always uses partial LIKE.
            $filters[] = $this->filterBuilder
                ->setField($repositoryField)
                ->setConditionType('like')
                ->setValue('%' . $spec['match'] . '%')
                ->create();
        }

        if (\array_key_exists('from', $spec) && $spec['from'] !== null) {
            $filters[] = $this->filterBuilder
                ->setField($repositoryField)
                ->setConditionType('gteq')
                ->setValue($spec['from'])
                ->create();
        }

        if (\array_key_exists('to', $spec) && $spec['to'] !== null) {
            $filters[] = $this->filterBuilder
                ->setField($repositoryField)
                ->setConditionType('lteq')
                ->setValue($spec['to'])
                ->create();
        }

        return $filters;
    }

    private function normalizeDirection(string $direction): string
    {
        $upper = strtoupper($direction);

        return $upper === SortOrder::SORT_DESC ? SortOrder::SORT_DESC : SortOrder::SORT_ASC;
    }
}

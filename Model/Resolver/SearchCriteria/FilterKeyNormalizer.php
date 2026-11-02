<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\SearchCriteria;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Maps the unsuffixed filter keys (category, store, ...) onto the legacy `*_id` keys, so the
 * rest of the pipeline only knows one spelling. Sending both spellings of one filter is
 * rejected rather than resolved by precedence.
 */
class FilterKeyNormalizer
{
    /**
     * @param array<string, mixed> $filter
     * @param array<string, string> $aliases unsuffixed key => legacy key
     * @throws GraphQlInputException
     * @return array<string, mixed>
     */
    public function normalize(array $filter, array $aliases): array
    {
        foreach ($aliases as $alias => $legacy) {
            if (!isset($filter[$alias])) {
                unset($filter[$alias]);
                continue;
            }
            if (isset($filter[$legacy])) {
                throw new GraphQlInputException(
                    __('The filters "%1" and "%2" are the same filter, use only one of them.', $alias, $legacy)
                );
            }
            $filter[$legacy] = $filter[$alias];
            unset($filter[$alias]);
        }

        return $filter;
    }
}

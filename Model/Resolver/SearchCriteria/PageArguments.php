<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\SearchCriteria;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * The one place the pageSize / currentPage arguments of the four list queries are validated.
 *
 * Without a cap a client could ask for pageSize 1000000, or 0, which drops the SQL LIMIT, and make
 * an unauthenticated request load the whole table.
 */
class PageArguments
{
    public const DEFAULT_PAGE_SIZE = 20;
    public const MAX_PAGE_SIZE = 100;

    /**
     * @param array<string, mixed> $args
     * @throws GraphQlInputException
     * @return array{pageSize: int, currentPage: int}
     */
    public function normalize(array $args): array
    {
        $pageSize = isset($args['pageSize']) ? (int) $args['pageSize'] : self::DEFAULT_PAGE_SIZE;
        $currentPage = isset($args['currentPage']) ? (int) $args['currentPage'] : 1;

        if ($pageSize < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }
        if ($currentPage < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }

        return [
            'pageSize' => min($pageSize, self::MAX_PAGE_SIZE),
            'currentPage' => $currentPage,
        ];
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;

/**
 * Caps how many post rows the nested `posts` fields of categories, tags and authors may ask for in one request.
 *
 * `posts` closes a cycle in the schema (posts -> categories -> posts ...), and the per-call pageSize cap
 * does not bound the product across levels. Every nested call spends its page size here, so the total
 * stays bounded no matter how deep or wide the query is. The shared instance lives for one request.
 */
class NestedPostsBudget
{
    public const MAX_ROWS = 1000;

    private int $spent = 0;

    /**
     * @throws GraphQlInputException
     */
    public function spend(int $rows): void
    {
        $this->spent += $rows;
        if ($this->spent > self::MAX_ROWS) {
            throw new GraphQlInputException(__(
                'The nested posts fields of this query ask for more than %1 posts in total. '
                . 'Lower pageSize or nest fewer posts lists.',
                self::MAX_ROWS,
            ));
        }
    }
}

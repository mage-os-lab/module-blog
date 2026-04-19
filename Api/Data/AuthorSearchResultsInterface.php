<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface AuthorSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return AuthorInterface[]
     */
    public function getItems(): array;

    /**
     * @param AuthorInterface[] $items
     */
    public function setItems(array $items): self;
}

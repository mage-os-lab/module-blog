<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface TagSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return TagInterface[]
     */
    public function getItems(): array;

    /**
     * @param TagInterface[] $items
     */
    public function setItems(array $items): self;
}

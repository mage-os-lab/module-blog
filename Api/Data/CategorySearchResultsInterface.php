<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface CategorySearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return CategoryInterface[]
     */
    public function getItems(): array;

    /**
     * @param CategoryInterface[] $items
     */
    public function setItems(array $items): self;
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface PostSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return PostInterface[]
     */
    public function getItems(): array;

    /**
     * @param PostInterface[] $items
     */
    public function setItems(array $items): self;
}

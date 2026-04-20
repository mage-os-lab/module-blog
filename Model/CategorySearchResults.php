<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchResults;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;

class CategorySearchResults extends SearchResults implements CategorySearchResultsInterface
{
    /**
     * @return CategoryInterface[]
     */
    public function getItems(): array
    {
        $items = parent::getItems();
        /** @phpstan-ignore-next-line return.type */
        return \is_array($items) ? $items : [];
    }

    /**
     * @param CategoryInterface[] $items
     */
    public function setItems(array $items): CategorySearchResultsInterface
    {
        /** @phpstan-ignore-next-line argument.type */
        parent::setItems($items);
        return $this;
    }
}

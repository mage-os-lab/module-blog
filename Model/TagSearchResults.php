<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchResults;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\Data\TagSearchResultsInterface;

class TagSearchResults extends SearchResults implements TagSearchResultsInterface
{
    /**
     * @return TagInterface[]
     */
    public function getItems(): array
    {
        $items = parent::getItems();
        /** @phpstan-ignore-next-line return.type */
        return \is_array($items) ? $items : [];
    }

    /**
     * @param TagInterface[] $items
     */
    public function setItems(array $items): TagSearchResultsInterface
    {
        /** @phpstan-ignore-next-line argument.type */
        parent::setItems($items);
        return $this;
    }
}

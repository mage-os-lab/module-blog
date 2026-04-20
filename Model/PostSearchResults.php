<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchResults;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;

class PostSearchResults extends SearchResults implements PostSearchResultsInterface
{
    /**
     * @return PostInterface[]
     */
    public function getItems(): array
    {
        $items = parent::getItems();
        /** @phpstan-ignore-next-line return.type */
        return \is_array($items) ? $items : [];
    }

    /**
     * @param PostInterface[] $items
     */
    public function setItems(array $items): PostSearchResultsInterface
    {
        /** @phpstan-ignore-next-line argument.type */
        parent::setItems($items);
        return $this;
    }
}

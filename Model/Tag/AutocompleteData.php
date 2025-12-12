<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Tag;

use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;

/**
 * Provides Data for Tag Autocomplete Ajax Call
 */
class AutocompleteData
{
    /**
     * @var BlogFactory
     */
    protected $collectionFactory;

    /**
     * Post constructor.
     * @param BlogFactory $blogFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param $search
     * @return array
     */
    public function getItems($search)
    {
        $collection = $this->collectionFactory->create();
        $collection
            ->addFieldToFilter(
                ['tag_id', 'title'],
                [
                    ['eq' => $search],
                    ['like' => '%' . $search . '%'],
                ]
            )
            ->setPageSize(15)
        ;

        $result = [];
        foreach ($collection as $item) {
            $result[] = [
                'value' => $item->getTitle(),
                'label' => $item->getTitle()
            ];
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Tag\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected $loadedData = [];

    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getData(): array
    {
        if ($this->loadedData !== []) {
            return $this->loadedData;
        }

        $tagId = (int) $this->request->getParam('tag_id');
        if ($tagId <= 0) {
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter('tag_id', (string) $tagId);

        foreach ($this->collection->getItems() as $tag) {
            $id = (int) $tag->getId();
            $this->loadedData[$id] = $tag->getData();
        }

        return $this->loadedData;
    }
}

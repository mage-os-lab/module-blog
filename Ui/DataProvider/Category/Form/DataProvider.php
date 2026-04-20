<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Category\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;

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
        private readonly UrlInterface $urlBuilder,
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

        $categoryId = (int) $this->request->getParam('category_id');
        if ($categoryId <= 0) {
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter('category_id', (string) $categoryId);

        foreach ($this->collection->getItems() as $category) {
            $id = (int) $category->getId();
            $this->loadedData[$id] = [
                'category' => $category->getData(),
            ];
        }

        return $this->loadedData;
    }
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Category\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use MageOS\Blog\Model\ResourceModel\Category\Collection as CategoryCollection;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;

class CategoryDataProvider extends AbstractDataProvider
{
    /**
     * @var CategoryCollection
     */
    protected $collection;
    protected DataPersistorInterface $dataPersistor;
    protected array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $categoryCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $categoryCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta): array
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (isset($this->loadedData) && !empty($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var $category \MageOS\Blog\Model\Category */
        foreach ($items as $category) {
            $category = $category->load($category->getId()); //temporary fix
            $data = $category->getData();
            /* Prepare Featured Image */
            $map = [
                'category_img' => 'getCategoryImage',
            ];
            foreach ($map as $key => $method) {
                if (isset($data[$key])) {
                    $name = $data[$key];
                    unset($data[$key]);
                    $data[$key][0] = [
                        'name' => $name,
                        'url' => $category->$method(),
                    ];
                }
            }
            $this->loadedData[$category->getId()] = $data;
        }

        $data = $this->dataPersistor->get('blog_category_form_data');
        if (!empty($data)) {
            $category = $this->collection->getNewEmptyItem();
            $category->setData($data);
            $this->loadedData[$category->getId()] = $category->getData();
            $this->dataPersistor->clear('blog_category_form_data');
        }

        return $this->loadedData;
    }
}

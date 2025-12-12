<?php
declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Post\Form;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ResourceModel\Post\Collection as PostCollection;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory;

class PostDataProvider extends AbstractDataProvider
{
    /**
     * @var PostCollection
     */
    protected $collection;
    protected DataPersistorInterface $dataPersistor;
    protected array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $postCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $postCollectionFactory->create();
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
        /** @var $post \MageOS\Blog\Model\Post */
        foreach ($items as $post) {
            $post = $post->load($post->getId()); //temporary fix
            $data = $post->getData();

            /* Prepare Featured Image */
            $map = [
                'featured_img' => 'getFeaturedImage',
                'featured_list_img' => 'getFeaturedListImage',
                'og_img' => 'getOgImage'
            ];
            foreach ($map as $key => $method) {
                if (isset($data[$key])) {
                    $name = $data[$key];
                    unset($data[$key]);
                    $data[$key][0] = [
                        'name' => $name,
                        'url' => $post->$method(),
                    ];
                }
            }

            $data['data'] = ['links' => []];

            /* Prepare related posts */
            $collection = $post->getRelatedPosts();
            $items = [];
            foreach ($collection as $item) {
                    $itemData = $item->getData();
                    $itemData['id'] = $item->getId();
                    /* Fix for big request data array */
                foreach (['content', 'short_content', 'meta_description'] as $field) {
                    if (isset($itemData[$field])) {
                        unset($itemData[$field]);
                    }
                }
                    /* End */
                    $items[] = $itemData;
            }
            $data['data']['links']['post'] = $items;

            /* Prepare related products */
            $collection = $post->getRelatedProducts()->addAttributeToSelect('name');
            $items = [];
            foreach ($collection as $item) {
                $itemData = $item->getData();
                $itemData['id'] = $item->getId();
                /* Fix for big request data array */
                foreach ($itemData as $key => $value) {
                    if (!in_array($key, ['entity_id', 'position', 'display_on_product', 'display_on_post', 'auto_related', 'related_by_rule', 'name', 'store_id', 'id'])) {
                        unset($itemData[$key]);
                    }
                }
                /* End */

                $items[] = $itemData;
            }
            $data['data']['links']['product'] = $items;

            /* Set data */
            $this->loadedData[$post->getId()] = $data;
        }

        $data = $this->dataPersistor->get('blog_post_form_data');
        if (!empty($data)) {
            $post = $this->collection->getNewEmptyItem();
            $post->setData($data);
            $this->loadedData[$post->getId()] = $post->getData();
            $this->dataPersistor->clear('blog_post_form_data');
        }

        return $this->loadedData;
    }
}

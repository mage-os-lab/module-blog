<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Post\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ImageUploader;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory;

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
        private readonly ImageUploader $imageUploader,
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

        $postId = (int) $this->request->getParam('post_id');
        if ($postId <= 0) {
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter('post_id', (string) $postId);

        foreach ($this->collection->getItems() as $post) {
            $id = (int) $post->getId();
            $data = $post->getData();
            $this->loadedData[$id] = $this->decorateImages($data);
        }

        return $this->loadedData;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function decorateImages(array $data): array
    {
        foreach (['featured_image', 'og_image'] as $field) {
            if (empty($data[$field]) || !\is_string($data[$field])) {
                continue;
            }
            $data[$field] = [[
                'name' => $data[$field],
                'url' => $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                    . $this->imageUploader->getBasePath() . '/' . $data[$field],
            ]];
        }
        return $data;
    }
}

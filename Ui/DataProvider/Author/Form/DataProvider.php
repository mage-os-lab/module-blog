<?php

declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Author\Form;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use MageOS\Blog\Model\ImageUploader;
use MageOS\Blog\Model\ResourceModel\Author\CollectionFactory;

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

        $authorId = (int) $this->request->getParam('author_id');
        if ($authorId <= 0) {
            return $this->loadedData;
        }

        $this->collection->addFieldToFilter('author_id', (string) $authorId);

        foreach ($this->collection->getItems() as $author) {
            $id = (int) $author->getId();
            $data = $author->getData();
            $this->loadedData[$id] = $this->decorateAvatar($data);
        }

        return $this->loadedData;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function decorateAvatar(array $data): array
    {
        if (empty($data['avatar']) || !\is_string($data['avatar'])) {
            return $data;
        }
        $data['avatar'] = [[
            'name' => $data['avatar'],
            'url' => $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                . $this->imageUploader->getBasePath() . '/' . $data['avatar'],
        ]];
        return $data;
    }
}

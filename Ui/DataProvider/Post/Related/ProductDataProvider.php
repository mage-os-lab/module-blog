<?php
declare(strict_types=1);

namespace MageOS\Blog\Ui\DataProvider\Post\Related;

use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider as DataProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use MageOS\Blog\Api\Data\BlogPostInterface;
//use MageOS\Blog\Api\PostRepositoryInterface;

class ProductDataProvider extends DataProvider
{
    protected RequestInterface $request;
    protected ProductRepositoryInterface $productRepository;
    protected ProductLinkRepositoryInterface $productLinkRepository;
    private BlogPostInterface $post;
    //private PostRepositoryInterface $postRepository;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        ProductRepositoryInterface $productRepository,
        ProductLinkRepositoryInterface $productLinkRepository,
        $addFieldStrategies,
        $addFilterStrategies,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );

        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->productLinkRepository = $productLinkRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection()
    {
        /** @var Collection $collection */
        $collection = parent::getCollection();
        $collection->addAttributeToSelect('status');

        return $collection;
    }

    /**
     * Add specific filters
     *
     * @param Collection $collection
     * @return Collection
     */
    protected function addCollectionFilters(Collection $collection)
    {
        return $collection;
    }

    /**
     * Retrieve post
     *
     * @return BlogPostInterface|null
     */
    protected function getPost()
    {
        if (null !== $this->post) {
            return $this->post;
        }

        if (!($id = $this->request->getParam('current_post_id'))) {
            return null;
        }

        return $this->post = $this->postRepository->getById($id);
    }
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use MageOS\Blog\Model\AbstractRepository;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Post as PostResourceModel;
use MageOS\Blog\Model\PostFactory;
use MageOS\Blog\Api\Data\BlogPostInterface;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;

class PostRepository extends AbstractRepository implements PostRepositoryInterface
{
    private PostFactory $postFactory;
    public function __construct(
        PostResourceModel $resource,
        PostFactory $postFactory,
        CollectionFactory $postCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        parent::__construct(
            $resource,
            $postFactory,
            $postCollectionFactory,
            $searchResultsFactory,
            $collectionProcessor
        );
        $this->postFactory = $postFactory;
    }

    /**
     * @return PostFactory
     */
    public function getFactory(): PostFactory
    {
        return $this->postFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Post $post): mixed
    {
        return $this->genericSave($post);
    }

    /**
     * {@inheritdoc}
     */
    public function getById($postId, $editMode = false, $storeId = null, $forceReload = false): mixed
    {
       return $this->genericGet($postId);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Post $post): bool
    {
        return $this->genericDelete($post);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $postId): bool
    {
        return $this->genericDeleteById((string)$postId);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        return $this->genericGetList($searchCriteria);
    }
}

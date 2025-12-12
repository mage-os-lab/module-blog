<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Model\AbstractRepository;
use MageOS\Blog\Api\CommentRepositoryInterface;
use MageOS\Blog\Model\CommentFactory;
use MageOS\Blog\Model\ResourceModel\Comment as CommentResourceModel;
use MageOS\Blog\Model\ResourceModel\Comment\CollectionFactory;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class CommentRepository extends AbstractRepository implements CommentRepositoryInterface
{
    private CommentFactory $commentFactory;

    public function __construct(
        CommentFactory $commentFactory,
        CommentResourceModel $commentResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        parent::__construct(
            $commentResourceModel,
            $commentFactory,
            $collectionFactory,
            $searchResultsFactory,
            $collectionProcessor
        );
    }

    /**
     * @return CommentFactory
     */
    public function getFactory(): CommentFactory
    {
        return $this->commentFactory;
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotSaveException
     */
    public function save(Comment $comment)
    {
        return $this->genericSave($comment);
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getById($commentId, $editMode = false, $storeId = null, $forceReload = false)
    {
        return $this->genericGet($commentId);
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotDeleteException
     */
    public function delete(Comment $comment)
    {
        return $this->genericDelete($comment);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($commentId): bool
    {
        return $this->genericDeleteById((string)$commentId);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
       return $this->genericGetList($searchCriteria);
    }
}

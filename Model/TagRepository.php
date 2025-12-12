<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Model\AbstractRepository;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\TagFactory;
use MageOS\Blog\Model\ResourceModel\Tag as TagResourceModel;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

class TagRepository extends AbstractRepository implements TagRepositoryInterface
{
    private TagFactory $tagFactory;

    public function __construct(
        TagFactory $tagFactory,
        TagResourceModel $tagResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        parent::__construct(
            $tagResourceModel,
            $tagFactory,
            $collectionFactory,
            $searchResultsFactory,
            $collectionProcessor
        );
        $this->tagFactory = $tagFactory;
    }

    /**
     * @return TagFactory
     */
    public function getFactory()
    {
        return $this->tagFactory;
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotSaveException
     */
    public function save(Tag $tag)
    {
        return $this->genericSave($tag);
    }

    /**
     * {@inheritdoc}
     * @throws NoSuchEntityException
     */
    public function getById($tagId, $editMode = false, $storeId = null, $forceReload = false)
    {
        return $this->genericGet($tagId);
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotDeleteException
     */
    public function delete(Tag $tag)
    {
        return $this->genericDelete($tag);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($tagId): bool
    {
        return $this->genericDeleteById((string)$tagId);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        return $this->genericGetList($searchCriteria);
    }
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Model\AbstractRepository;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResourceModel;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Api\SearchResultsFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Class CategoryRepository model
 */
class CategoryRepository extends AbstractRepository implements CategoryRepositoryInterface
{
    private CategoryFactory $categoryFactory;
    private array $instances;

    public function __construct(
        CategoryFactory $categoryFactory,
        CategoryResourceModel $categoryResourceModel,
        CollectionFactory $collectionFactory,
        SearchResultsFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor = null
    ) {
        parent::__construct(
            $categoryResourceModel,
            $categoryFactory,
            $collectionFactory,
            $searchResultsFactory,
            $collectionProcessor
        );
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * @return CategoryFactory
     */
    public function getFactory(): CategoryFactory
    {
        return $this->categoryFactory;
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotSaveException
     */
    public function save(Category $category)
    {
        return $this->genericSave($category);
    }

    /**
     * {@inheritdoc}
     */
    public function getById($categoryId, $editMode = false, $storeId = null, $forceReload = false)
    {
        $cacheKey = implode('_', func_get_args());
        if (!isset($this->instances[$cacheKey])) {
            $category = $this->genericGet($categoryId);
            $this->instances[$cacheKey] = $category;
        }
        return $this->instances[$cacheKey];
    }

    /**
     * {@inheritdoc}
     * @throws CouldNotDeleteException
     */
    public function delete(Category $category)
    {
       return $this->genericDelete($category);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($categoryId): bool
    {
        return $this->genericDeleteById((string)$categoryId);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
       return $this->genericGetList($searchCriteria);
    }
}

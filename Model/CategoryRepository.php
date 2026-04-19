<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterfaceFactory;
use MageOS\Blog\Model\Category\Link\StoreLinkManager;
use MageOS\Blog\Model\ResourceModel\Category as CategoryResource;
use MageOS\Blog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly CategoryResource $resource,
        private readonly CategoryFactory $categoryFactory,
        private readonly CategoryCollectionFactory $collectionFactory,
        private readonly CategorySearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly StoreLinkManager $storeLinks,
    ) {
    }

    public function save(CategoryInterface $category): CategoryInterface
    {
        if (!$category instanceof Category) {
            throw new CouldNotSaveException(__('Unsupported category entity: %1', $category::class));
        }

        try {
            $this->resource->save($category);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog category: %1', $e->getMessage()), $e);
        }

        $categoryId = (int) $category->getCategoryId();
        $this->storeLinks->sync($categoryId, $category->getStoreIds());

        return $this->getById($categoryId);
    }

    public function getById(int $id): CategoryInterface
    {
        $category = $this->categoryFactory->create();
        $this->resource->load($category, $id);
        if (!$category->getCategoryId()) {
            throw NoSuchEntityException::singleField('categoryId', $id);
        }
        $this->hydrateLinks($category);

        return $category;
    }

    public function getByUrlKey(string $urlKey, int $storeId): CategoryInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(CategoryInterface::URL_KEY, $urlKey);
        $collection->getSelect()
            ->join(
                ['s' => $this->resource->getTable('mageos_blog_category_store')],
                's.category_id = main_table.category_id',
                []
            )
            ->where('s.store_id IN (?)', [$storeId, 0])
            ->group('main_table.category_id');

        $category = $collection->getFirstItem();
        if (!$category->getCategoryId()) {
            throw NoSuchEntityException::doubleField('urlKey', $urlKey, 'storeId', $storeId);
        }
        if (!$category instanceof Category) {
            throw new \LogicException('Collection returned unexpected entity class.');
        }
        $this->hydrateLinks($category);

        return $category;
    }

    public function getList(SearchCriteriaInterface $criteria): CategorySearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        /** @var CategoryInterface[] $items */
        $items = $collection->getItems();
        $results->setItems($items);
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    public function delete(CategoryInterface $category): bool
    {
        if (!$category instanceof Category) {
            throw new CouldNotDeleteException(__('Unsupported category entity: %1', $category::class));
        }
        try {
            $this->resource->delete($category);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog category: %1', $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    private function hydrateLinks(CategoryInterface $category): void
    {
        $id = (int) $category->getCategoryId();
        $category->setStoreIds($this->storeLinks->getLinkedIds($id));
    }
}

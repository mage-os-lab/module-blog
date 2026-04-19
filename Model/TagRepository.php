<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\Data\TagSearchResultsInterface;
use MageOS\Blog\Api\Data\TagSearchResultsInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\ResourceModel\Tag as TagResource;
use MageOS\Blog\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use MageOS\Blog\Model\Tag\Link\StoreLinkManager;

final class TagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly TagResource $resource,
        private readonly TagFactory $tagFactory,
        private readonly TagCollectionFactory $collectionFactory,
        private readonly TagSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly StoreLinkManager $storeLinks,
    ) {
    }

    public function save(TagInterface $tag): TagInterface
    {
        try {
            $this->resource->save($tag);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog tag: %1', $e->getMessage()), $e);
        }

        $tagId = (int) $tag->getTagId();
        $this->storeLinks->sync($tagId, $tag->getStoreIds());

        return $this->getById($tagId);
    }

    public function getById(int $id): TagInterface
    {
        $tag = $this->tagFactory->create();
        $this->resource->load($tag, $id);
        if (!$tag->getTagId()) {
            throw NoSuchEntityException::singleField('tagId', $id);
        }
        $this->hydrateLinks($tag);

        return $tag;
    }

    public function getByUrlKey(string $urlKey, int $storeId): TagInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(TagInterface::URL_KEY, $urlKey);
        $collection->getSelect()
            ->join(
                ['s' => $this->resource->getTable('mageos_blog_tag_store')],
                's.tag_id = main_table.tag_id',
                []
            )
            ->where('s.store_id IN (?)', [$storeId, 0])
            ->group('main_table.tag_id');

        $tag = $collection->getFirstItem();
        if (!$tag->getTagId()) {
            throw NoSuchEntityException::doubleField('urlKey', $urlKey, 'storeId', $storeId);
        }
        $this->hydrateLinks($tag);

        return $tag;
    }

    public function getList(SearchCriteriaInterface $criteria): TagSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    public function delete(TagInterface $tag): bool
    {
        try {
            $this->resource->delete($tag);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog tag: %1', $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    private function hydrateLinks(TagInterface $tag): void
    {
        $id = (int) $tag->getTagId();
        $tag->setStoreIds($this->storeLinks->getLinkedIds($id));
    }
}

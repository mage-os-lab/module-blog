<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Post\Link\CategoryLinkManager;
use MageOS\Blog\Model\Post\Link\StoreLinkManager;
use MageOS\Blog\Model\Post\Link\TagLinkManager;
use MageOS\Blog\Model\ResourceModel\Post as PostResource;
use MageOS\Blog\Model\ResourceModel\Post\CollectionFactory as PostCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

final class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly PostResource $resource,
        private readonly PostFactory $postFactory,
        private readonly PostCollectionFactory $collectionFactory,
        private readonly PostSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly StoreLinkManager $storeLinks,
        private readonly CategoryLinkManager $categoryLinks,
        private readonly TagLinkManager $tagLinks,
    ) {
    }

    public function save(PostInterface $post): PostInterface
    {
        try {
            $this->resource->save($post);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog post: %1', $e->getMessage()), $e);
        }

        $postId = (int) $post->getPostId();
        $this->storeLinks->sync($postId, $post->getStoreIds());
        $this->categoryLinks->sync($postId, $post->getCategoryIds());
        $this->tagLinks->sync($postId, $post->getTagIds());

        return $this->getById($postId);
    }

    public function getById(int $id): PostInterface
    {
        $post = $this->postFactory->create();
        $this->resource->load($post, $id);
        if (!$post->getPostId()) {
            throw NoSuchEntityException::singleField('postId', $id);
        }
        $this->hydrateLinks($post);

        return $post;
    }

    public function getByUrlKey(string $urlKey, int $storeId): PostInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(PostInterface::URL_KEY, $urlKey);
        $collection->getSelect()
            ->join(
                ['s' => $this->resource->getTable('mageos_blog_post_store')],
                's.post_id = main_table.post_id',
                []
            )
            ->where('s.store_id IN (?)', [$storeId, 0])
            ->group('main_table.post_id');

        $post = $collection->getFirstItem();
        if (!$post->getPostId()) {
            throw NoSuchEntityException::doubleField('urlKey', $urlKey, 'storeId', $storeId);
        }
        $this->hydrateLinks($post);

        return $post;
    }

    public function getList(SearchCriteriaInterface $criteria): PostSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        $results->setItems($collection->getItems());
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    public function delete(PostInterface $post): bool
    {
        try {
            $this->resource->delete($post);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog post: %1', $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    private function hydrateLinks(PostInterface $post): void
    {
        $id = (int) $post->getPostId();
        $post->setStoreIds($this->storeLinks->getLinkedIds($id));
        $post->setCategoryIds($this->categoryLinks->getLinkedIds($id));
        $post->setTagIds($this->tagLinks->getLinkedIds($id));
    }
}

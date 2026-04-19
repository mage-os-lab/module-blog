<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\AuthorSearchResultsInterface;
use MageOS\Blog\Api\Data\AuthorSearchResultsInterfaceFactory;
use MageOS\Blog\Model\ResourceModel\Author as AuthorResource;
use MageOS\Blog\Model\ResourceModel\Author\CollectionFactory as AuthorCollectionFactory;

final class AuthorRepository implements AuthorRepositoryInterface
{
    public function __construct(
        private readonly AuthorResource $resource,
        private readonly AuthorFactory $authorFactory,
        private readonly AuthorCollectionFactory $collectionFactory,
        private readonly AuthorSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
    ) {
    }

    public function save(AuthorInterface $author): AuthorInterface
    {
        if (!$author instanceof Author) {
            throw new CouldNotSaveException(__('Unsupported author entity: %1', $author::class));
        }

        try {
            $this->resource->save($author);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog author: %1', $e->getMessage()), $e);
        }

        return $this->getById((int) $author->getAuthorId());
    }

    public function getById(int $id): AuthorInterface
    {
        $author = $this->authorFactory->create();
        $this->resource->load($author, $id);
        if (!$author->getAuthorId()) {
            throw NoSuchEntityException::singleField('authorId', $id);
        }

        return $author;
    }

    public function getBySlug(string $slug): AuthorInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(AuthorInterface::SLUG, $slug);

        $author = $collection->getFirstItem();
        if (!$author->getAuthorId()) {
            throw NoSuchEntityException::singleField('slug', $slug);
        }
        if (!$author instanceof Author) {
            throw new \LogicException('Collection returned unexpected entity class.');
        }

        return $author;
    }

    public function getList(SearchCriteriaInterface $criteria): AuthorSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($criteria, $collection);

        $results = $this->searchResultsFactory->create();
        $results->setSearchCriteria($criteria);
        /** @var AuthorInterface[] $items */
        $items = $collection->getItems();
        $results->setItems($items);
        $results->setTotalCount($collection->getSize());

        return $results;
    }

    public function delete(AuthorInterface $author): bool
    {
        if (!$author instanceof Author) {
            throw new CouldNotDeleteException(__('Unsupported author entity: %1', $author::class));
        }
        try {
            $this->resource->delete($author);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog author: %1', $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}

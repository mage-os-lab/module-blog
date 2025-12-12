<?php

namespace MageOS\Blog\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

abstract class AbstractRepository
{
    public $modelFactory;
    public $modelCollectionFactory;
    public $collectionProcessor;
    public $searchResultsFactory;
    public $resource;

    public function __construct(
        $resource,
        $modelFactory,
        $modelCollectionFactory,
        $searchResultsFactory,
        $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
        $this->modelCollectionFactory = $modelCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function genericSave($model)
    {
        if ($model){
            try {
                $this->resource->save($model);
            } catch (\Exception $exception) {
                throw new CouldNotSaveException(__(
                    'Could not save the entity id: %1',
                    $exception->getMessage()
                ));
            }
            return $this->genericGet($model->getId());
        }
        return false;
    }

    public function genericGet(string $modelId)
    {
        $model = $this->modelFactory->create();
        $this->resource->load($model, $modelId);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Entity with id "%1" does not exist.', $modelId));
        }
        return $model;
    }

    public function genericGetList(
        SearchCriteriaInterface $searchCriteria
    )
    {
        $collection = $this->modelCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems(array_values($collection->getItems()));
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    public function genericDelete($model): bool
    {
        try {
            $modelFactory = $this->modelFactory->create();
            $this->resource->load($modelFactory, $model->getId());
            $this->resource->delete($modelFactory);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the entity: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    public function genericDeleteById(string $modelId): bool
    {
        return $this->genericDelete($this->get($modelId));
    }
}

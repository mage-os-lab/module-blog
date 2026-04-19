<?php

declare(strict_types=1);

namespace MageOS\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategorySearchResultsInterface;

interface CategoryRepositoryInterface
{
    /** @throws CouldNotSaveException */
    public function save(CategoryInterface $category): CategoryInterface;

    /** @throws NoSuchEntityException */
    public function getById(int $id): CategoryInterface;

    /** @throws NoSuchEntityException */
    public function getByUrlKey(string $urlKey, int $storeId): CategoryInterface;

    public function getList(SearchCriteriaInterface $criteria): CategorySearchResultsInterface;

    public function delete(CategoryInterface $category): bool;

    /** @throws NoSuchEntityException */
    public function deleteById(int $id): bool;
}

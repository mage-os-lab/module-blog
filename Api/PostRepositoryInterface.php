<?php

declare(strict_types=1);

namespace MageOS\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostSearchResultsInterface;

interface PostRepositoryInterface
{
    /** @throws CouldNotSaveException */
    public function save(PostInterface $post): PostInterface;

    /** @throws NoSuchEntityException */
    public function getById(int $id): PostInterface;

    /** @throws NoSuchEntityException */
    public function getByUrlKey(string $urlKey, int $storeId): PostInterface;

    public function getList(SearchCriteriaInterface $criteria): PostSearchResultsInterface;

    public function delete(PostInterface $post): bool;

    /** @throws NoSuchEntityException */
    public function deleteById(int $id): bool;
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\AuthorSearchResultsInterface;

interface AuthorRepositoryInterface
{
    /** @throws CouldNotSaveException */
    public function save(AuthorInterface $author): AuthorInterface;

    /** @throws NoSuchEntityException */
    public function getById(int $id): AuthorInterface;

    /** @throws NoSuchEntityException */
    public function getBySlug(string $slug): AuthorInterface;

    public function getList(SearchCriteriaInterface $criteria): AuthorSearchResultsInterface;

    public function delete(AuthorInterface $author): bool;

    /** @throws NoSuchEntityException */
    public function deleteById(int $id): bool;
}

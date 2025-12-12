<?php

namespace MageOS\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Model\Post;
use MageOS\Blog\Model\PostFactory;

/**
 * Interface PostRepositoryInterface
 */
interface PostRepositoryInterface
{
    /**
     * @return PostFactory
     */
    public function getFactory(): PostFactory;

    /**
     * @param Post $post
     * @return mixed
     * @throws CouldNotSaveException
     */
    public function save(Post $post): mixed;

    /**
     * @param $postId
     * @return mixed
     */
    public function getById($postId): mixed;

    /**
     * Retrieve model matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    ): SearchResultsInterface;

    /**
     * @param Post $post
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(Post $post): bool;

    /**
     * Delete Post by ID.
     *
     * @param int $postId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $postId): bool;
}

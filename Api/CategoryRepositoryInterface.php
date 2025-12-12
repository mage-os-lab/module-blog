<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\CategoryFactory;

/**
 * Interface PostRepositoryInterface
 */
interface CategoryRepositoryInterface
{
    /**
     * @return CategoryFactory
     */
    public function getFactory();

    /**
     * @param Category $category
     * @return mixed
     */
    public function save(Category $category);

    /**
     * @param $categoryId
     * @return mixed
     */
    public function getById($categoryId);

    /**
     * Retrieve Category matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResults
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param Category $category
     * @return mixed
     */
    public function delete(Category $category);

    /**
     * Delete Category by ID.
     *
     * @param int $categoryId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($categoryId);
}

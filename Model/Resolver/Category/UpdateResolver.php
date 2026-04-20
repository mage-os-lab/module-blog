<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;

class UpdateResolver implements ResolverInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryMapper $categoryMapper,
        private readonly AdminAuthorization $adminAuthorization,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): array {
        $this->adminAuthorization->assertAuthorized($context, 'MageOS_Blog::category');

        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Missing "id" argument.'));
        }
        $input = $args['input'] ?? null;
        if (!\is_array($input)) {
            throw new GraphQlInputException(__('Missing "input" argument.'));
        }

        try {
            $category = $this->categoryRepository->getById((int) $args['id']);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        if (\array_key_exists('url_key', $input)) {
            $category->setUrlKey((string) $input['url_key']);
        }
        if (\array_key_exists('title', $input)) {
            $category->setTitle((string) $input['title']);
        }
        if (\array_key_exists('description', $input)) {
            $category->setDescription($input['description'] === null ? null : (string) $input['description']);
        }
        if (\array_key_exists('parent_id', $input)) {
            $category->setParentId($input['parent_id'] === null ? null : (int) $input['parent_id']);
        }
        if (\array_key_exists('position', $input) && $input['position'] !== null) {
            $category->setPosition((int) $input['position']);
        }
        if (\array_key_exists('meta_title', $input)) {
            $category->setMetaTitle($input['meta_title'] === null ? null : (string) $input['meta_title']);
        }
        if (\array_key_exists('meta_description', $input)) {
            $category->setMetaDescription(
                $input['meta_description'] === null ? null : (string) $input['meta_description'],
            );
        }
        if (\array_key_exists('meta_keywords', $input)) {
            $category->setMetaKeywords($input['meta_keywords'] === null ? null : (string) $input['meta_keywords']);
        }
        if (\array_key_exists('include_in_menu', $input) && $input['include_in_menu'] !== null) {
            $category->setIncludeInMenu((bool) $input['include_in_menu']);
        }
        if (\array_key_exists('include_in_sidebar', $input) && $input['include_in_sidebar'] !== null) {
            $category->setIncludeInSidebar((bool) $input['include_in_sidebar']);
        }
        if (\array_key_exists('is_active', $input) && $input['is_active'] !== null) {
            $category->setIsActive((bool) $input['is_active']);
        }
        if (\array_key_exists('store_ids', $input) && \is_array($input['store_ids'])) {
            $category->setStoreIds(array_map('intval', $input['store_ids']));
        }

        try {
            $saved = $this->categoryRepository->save($category);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $this->categoryMapper->toArray($saved);
    }
}

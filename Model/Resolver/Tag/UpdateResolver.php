<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Tag;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;

class UpdateResolver implements ResolverInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly TagMapper $tagMapper,
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
        $this->adminAuthorization->assertAuthorized($context, 'MageOS_Blog::tag');

        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('Missing "id" argument.'));
        }
        $input = $args['input'] ?? null;
        if (!\is_array($input)) {
            throw new GraphQlInputException(__('Missing "input" argument.'));
        }

        try {
            $tag = $this->tagRepository->getById((int) $args['id']);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        if (\array_key_exists('url_key', $input)) {
            $tag->setUrlKey((string) $input['url_key']);
        }
        if (\array_key_exists('title', $input)) {
            $tag->setTitle((string) $input['title']);
        }
        if (\array_key_exists('description', $input)) {
            $tag->setDescription($input['description'] === null ? null : (string) $input['description']);
        }
        if (\array_key_exists('meta_title', $input)) {
            $tag->setMetaTitle($input['meta_title'] === null ? null : (string) $input['meta_title']);
        }
        if (\array_key_exists('meta_description', $input)) {
            $tag->setMetaDescription($input['meta_description'] === null ? null : (string) $input['meta_description']);
        }
        if (\array_key_exists('is_active', $input) && $input['is_active'] !== null) {
            $tag->setIsActive((bool) $input['is_active']);
        }
        if (\array_key_exists('store_ids', $input) && \is_array($input['store_ids'])) {
            $tag->setStoreIds(array_map('intval', $input['store_ids']));
        }

        try {
            $saved = $this->tagRepository->save($tag);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return $this->tagMapper->toArray($saved);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;

class DetailResolver implements ResolverInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly PostMapper $postMapper,
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
        $args ??= [];
        $hasId = isset($args['id']);
        $hasUrlKey = isset($args['url_key']) && $args['url_key'] !== '';

        if ($hasId === $hasUrlKey) {
            throw new GraphQlInputException(__('Either "id" or "url_key" must be specified.'));
        }

        try {
            if ($hasId) {
                $post = $this->postRepository->getById((int) $args['id']);
            } else {
                $post = $this->postRepository->getByUrlKey(
                    (string) $args['url_key'],
                    (int) $this->storeManager->getStore()->getId(),
                );
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $this->postMapper->toArray($post);
    }
}

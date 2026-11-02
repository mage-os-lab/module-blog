<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Tag;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;

class DetailResolver implements ResolverInterface
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly TagMapper $tagMapper,
        private readonly ReadPolicy $readPolicy,
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

        $storeId = (int) $this->storeManager->getStore()->getId();

        try {
            $tag = $hasId
                ? $this->tagRepository->getById((int) $args['id'])
                : $this->tagRepository->getByUrlKey((string) $args['url_key'], $storeId);

            if (!$this->readPolicy->canReadTag($context, $tag)) {
                // Fail like a missing tag, so a hidden one can't be told apart from one that doesn't exist.
                throw $hasId
                    ? NoSuchEntityException::singleField('tagId', (int) $args['id'])
                    : NoSuchEntityException::doubleField('urlKey', (string) $args['url_key'], 'storeId', $storeId);
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $this->tagMapper->toArray($tag);
    }
}

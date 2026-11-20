<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\CategoryMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\UidCodec;

class DetailResolver implements ResolverInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryMapper $categoryMapper,
        private readonly ReadPolicy $readPolicy,
        private readonly UidCodec $uidCodec,
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
        $hasUid = isset($args['uid']) && $args['uid'] !== '';
        $hasLookup = isset($args['url_key']) && $args['url_key'] !== '';

        if ((int) $hasId + (int) $hasUid + (int) $hasLookup !== 1) {
            throw new GraphQlInputException(__('Exactly one of "id", "uid" or "url_key" must be specified.'));
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        if ($hasUid) {
            $category = $this->findByUid($context, (string) $args['uid']);

            return $this->categoryMapper->toArray($category);
        }

        try {
            $category = $hasId
                ? $this->categoryRepository->getById((int) $args['id'])
                : $this->categoryRepository->getByUrlKey((string) $args['url_key'], $storeId);

            if (!$this->readPolicy->canReadCategory($context, $category)) {
                // Fail like a missing category, so a hidden one can't be told apart from one that doesn't exist.
                throw $hasId
                    ? NoSuchEntityException::singleField('categoryId', (int) $args['id'])
                    : NoSuchEntityException::doubleField('urlKey', (string) $args['url_key'], 'storeId', $storeId);
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $this->categoryMapper->toArray($category);
    }

    /**
     * A malformed uid, an unknown one and a hidden entity all fail with the same message, with one fixed
     * message that does not even echo the uid, so a uid can't be used to probe for entities.
     */
    private function findByUid(ContextInterface $context, string $uid): CategoryInterface
    {
        $id = $this->uidCodec->decode($uid);

        try {
            $category = $id === null ? null : $this->categoryRepository->getById($id);
        } catch (NoSuchEntityException) {
            $category = null;
        }

        if ($category === null || !$this->readPolicy->canReadCategory($context, $category)) {
            throw new GraphQlNoSuchEntityException(__('The requested blog category was not found.'));
        }

        return $category;
    }
}

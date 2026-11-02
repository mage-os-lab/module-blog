<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\UidCodec;

class DetailResolver implements ResolverInterface
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly AuthorMapper $authorMapper,
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
        $hasLookup = isset($args['slug']) && $args['slug'] !== '';

        if ((int) $hasId + (int) $hasUid + (int) $hasLookup !== 1) {
            throw new GraphQlInputException(__('Exactly one of "id", "uid" or "slug" must be specified.'));
        }

        if ($hasUid) {
            $author = $this->findByUid($context, (string) $args['uid']);

            return $this->authorMapper->toArray($author);
        }

        try {
            $author = $hasId
                ? $this->authorRepository->getById((int) $args['id'])
                : $this->authorRepository->getBySlug((string) $args['slug']);

            if (!$this->readPolicy->canReadAuthor($context, $author)) {
                // Fail like a missing author, so a hidden one can't be told apart from one that doesn't exist.
                throw $hasId
                    ? NoSuchEntityException::singleField('authorId', (int) $args['id'])
                    : NoSuchEntityException::singleField('slug', (string) $args['slug']);
            }
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $this->authorMapper->toArray($author);
    }

    /**
     * A malformed uid, an unknown one and a hidden entity all fail with the same message, with one fixed
     * message that does not even echo the uid, so a uid can't be used to probe for entities.
     */
    private function findByUid(ContextInterface $context, string $uid): AuthorInterface
    {
        $id = $this->uidCodec->decode($uid);

        try {
            $author = $id === null ? null : $this->authorRepository->getById($id);
        } catch (NoSuchEntityException) {
            $author = null;
        }

        if ($author === null || !$this->readPolicy->canReadAuthor($context, $author)) {
            throw new GraphQlNoSuchEntityException(__('The requested blog author was not found.'));
        }

        return $author;
    }
}

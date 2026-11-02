<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;

class DetailResolver implements ResolverInterface
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly AuthorMapper $authorMapper,
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
        $hasSlug = isset($args['slug']) && $args['slug'] !== '';

        if ($hasId === $hasSlug) {
            throw new GraphQlInputException(__('Either "id" or "slug" must be specified.'));
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
}

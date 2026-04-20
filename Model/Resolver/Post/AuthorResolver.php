<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;

class AuthorResolver implements ResolverInterface
{
    public function __construct(
        private readonly AuthorRepositoryInterface $authorRepository,
        private readonly AuthorMapper $authorMapper,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     * @return array<string, mixed>|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?array {
        $authorId = isset($value['author_id']) ? (int) $value['author_id'] : 0;
        if ($authorId === 0) {
            return null;
        }

        try {
            $author = $this->authorRepository->getById($authorId);
        } catch (NoSuchEntityException) {
            return null;
        }

        return $this->authorMapper->toArray($author);
    }
}

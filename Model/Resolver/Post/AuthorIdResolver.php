<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\ReadPolicy;

/**
 * BlogPost.author_id (deprecated, use author): the id only when the caller may read that author.
 *
 * A hidden or deleted author resolves to null, the same as a post without an author.
 */
class AuthorIdResolver implements ResolverInterface
{
    public function __construct(
        private readonly NestedEntityLoader $loader,
        private readonly ReadPolicy $readPolicy,
    ) {
    }

    /**
     * @param array<string, mixed>|null $value
     * @param array<string, mixed>|null $args
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null,
    ): ?int {
        $authorId = isset($value['author_id']) ? (int) $value['author_id'] : 0;
        if ($authorId === 0) {
            return null;
        }

        $author = $this->loader->author($authorId);

        return $author !== null && $this->readPolicy->canReadAuthor($context, $author) ? $authorId : null;
    }
}

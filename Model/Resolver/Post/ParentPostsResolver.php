<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * The `posts` field of a category, tag or author: the root `blogPosts` list constrained to the parent.
 *
 * Reaching this field already took a read-policy-guarded resolver for the parent. The posts are still
 * read through ListResolver, so the post rules (published, current store view, blog enabled, admin
 * ACL) are the ones of `blogPosts` and a readable parent never leaks a post the caller may not read.
 */
abstract class ParentPostsResolver implements ResolverInterface
{
    /** The post repository field that links a post to the parent. */
    abstract protected function postField(): string;

    public function __construct(
        private readonly ListResolver $postList,
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
        // 0 is never a real id, so a parent without one lists nothing.
        $parentId = (int) ($value['id'] ?? 0);

        return $this->postList->listForParent($context, $args ?? [], [$this->postField() => $parentId]);
    }
}

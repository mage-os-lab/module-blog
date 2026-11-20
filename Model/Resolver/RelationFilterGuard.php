<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;

/**
 * Keeps a list filter from being used to read or detect an entity the caller may not read.
 *
 * A filter on a category, tag or author id (or a parent category id) that the caller may not read
 * is rewritten to an id that matches nothing, which is exactly what an id that does not exist gives.
 * Without this a visitor could list the published posts of a hidden category or author, and tell
 * a hidden id from a missing one by the empty result.
 */
class RelationFilterGuard
{
    public const KIND_CATEGORY = 'category';
    public const KIND_TAG = 'tag';
    public const KIND_AUTHOR = 'author';

    /** Largest `in` list a caller may send; every distinct id costs a repository load. */
    public const MAX_IN_IDS = 50;

    /** Matches no row, and is never a real id. */
    private const NO_MATCH = -1;

    public function __construct(
        private readonly ReadPolicy $readPolicy,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $filter filter input, keyed by legacy filter key
     * @param array<string, string> $relations filter key => KIND_*
     * @return array<string, mixed>
     */
    public function restrict(ContextInterface $context, array $filter, array $relations): array
    {
        /** @var array<string, int> $resolved kind and id => id or NO_MATCH, so an id is loaded once per call */
        $resolved = [];
        foreach ($relations as $key => $kind) {
            if (!isset($filter[$key]) || !\is_array($filter[$key])) {
                continue;
            }
            $spec = $filter[$key];
            if (isset($spec['eq'])) {
                $spec['eq'] = $this->readableId($context, $kind, $spec['eq'], $resolved);
            }
            if (isset($spec['in']) && \is_array($spec['in'])) {
                if (\count($spec['in']) > self::MAX_IN_IDS) {
                    throw new GraphQlInputException(
                        __('The "in" filter accepts at most %1 values.', self::MAX_IN_IDS)
                    );
                }
                $ids = [];
                foreach ($spec['in'] as $id) {
                    $ids[] = $this->readableId($context, $kind, $id, $resolved);
                }
                $spec['in'] = array_values(array_unique($ids));
            }
            $filter[$key] = $spec;
        }

        return $filter;
    }

    /**
     * @param array<string, int> $resolved
     */
    private function readableId(ContextInterface $context, string $kind, mixed $value, array &$resolved): int
    {
        if (!is_numeric($value)) {
            return self::NO_MATCH;
        }
        $id = (int) $value;
        if ($id === 0 && $kind === self::KIND_CATEGORY) {
            // The root: a parent filter of 0 means "top level", there is no category 0 to hide.
            return 0;
        }

        $memo = $kind . ':' . $id;
        $resolved[$memo] ??= $this->canRead($context, $kind, $id) ? $id : self::NO_MATCH;

        return $resolved[$memo];
    }

    private function canRead(ContextInterface $context, string $kind, int $id): bool
    {
        try {
            return match ($kind) {
                self::KIND_CATEGORY => $this->readPolicy->canReadCategory(
                    $context,
                    $this->categoryRepository->getById($id)
                ),
                self::KIND_TAG => $this->readPolicy->canReadTag($context, $this->tagRepository->getById($id)),
                self::KIND_AUTHOR => $this->readPolicy->canReadAuthor($context, $this->authorRepository->getById($id)),
                default => false,
            };
        } catch (NoSuchEntityException) {
            return false;
        }
    }
}

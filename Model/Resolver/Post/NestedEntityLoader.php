<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;

/**
 * Loads the categories, tags and authors behind the nested post fields once per id.
 *
 * It is a shared DI service, so the cache lives as long as the object manager does: one GraphQL
 * request under PHP-FPM. A list of 20 posts sharing 3 categories loads each category once instead
 * of 20 times.
 *
 * It hands out entities without any visibility check. The caller must run ReadPolicy on every
 * entity it gets, every time: the cache holds hidden entities as well.
 */
class NestedEntityLoader
{
    /** @var array<int, CategoryInterface|null> */
    private array $categories = [];

    /** @var array<int, TagInterface|null> */
    private array $tags = [];

    /** @var array<int, AuthorInterface|null> */
    private array $authors = [];

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly TagRepositoryInterface $tagRepository,
        private readonly AuthorRepositoryInterface $authorRepository,
    ) {
    }

    public function category(int $id): ?CategoryInterface
    {
        if (!\array_key_exists($id, $this->categories)) {
            try {
                $this->categories[$id] = $this->categoryRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->categories[$id] = null;
            }
        }

        return $this->categories[$id];
    }

    public function tag(int $id): ?TagInterface
    {
        if (!\array_key_exists($id, $this->tags)) {
            try {
                $this->tags[$id] = $this->tagRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->tags[$id] = null;
            }
        }

        return $this->tags[$id];
    }

    public function author(int $id): ?AuthorInterface
    {
        if (!\array_key_exists($id, $this->authors)) {
            try {
                $this->authors[$id] = $this->authorRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->authors[$id] = null;
            }
        }

        return $this->authors[$id];
    }
}

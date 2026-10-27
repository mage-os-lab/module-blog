<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\Resolver;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Author;
use MageOS\Blog\Model\Resolver\Category;
use MageOS\Blog\Model\Resolver\Post;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\Tag;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * The GraphQL read queries must show a caller what the storefront shows, and nothing more.
 * An admin token whose role grants the ACL resource keeps full access.
 *
 * Runs the real resolvers against the database. Only the GraphQL context and the ACL
 * are stubbed: whether a token is an admin one is decided by Magento before a resolver runs.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class ReadVisibilityTest extends TestCase
{
    use SecondStoreTrait;

    private const MISSING_ID = 987654321;

    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function test_visitor_lists_only_published_posts_visible_on_the_current_store(): void
    {
        $this->seedPosts();

        $result = $this->resolve(Post\ListResolver::class, $this->context(isAdmin: false));

        self::assertEqualsCanonicalizing(['pub-all', 'pub-store-1', 'pub-unassigned'], $this->urlKeys($result));
        self::assertSame(3, $result['total_count']);
    }

    public function test_visitor_browsing_another_store_sees_that_stores_posts(): void
    {
        $second = $this->secondStoreId();
        $this->seedPosts();
        $this->objectManager->get(StoreManagerInterface::class)->setCurrentStore($second);

        $result = $this->resolve(Post\ListResolver::class, $this->context(isAdmin: false));

        self::assertEqualsCanonicalizing(['pub-all', 'pub-store-2', 'pub-unassigned'], $this->urlKeys($result));
    }

    public function test_visitor_cannot_widen_the_post_list_with_a_status_filter(): void
    {
        $this->seedPosts();

        $hiddenStatuses = [
            BlogPostStatus::Draft->value,
            BlogPostStatus::Scheduled->value,
            BlogPostStatus::Archived->value,
        ];
        $result = $this->resolve(
            Post\ListResolver::class,
            $this->context(isAdmin: false),
            ['filter' => ['status' => ['in' => $hiddenStatuses]]],
        );

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['total_count']);
    }

    public function test_visitor_cannot_pull_a_hidden_post_by_its_id_through_the_list(): void
    {
        $ids = $this->seedPosts();

        $result = $this->resolve(
            Post\ListResolver::class,
            $this->context(isAdmin: false),
            ['filter' => ['id' => ['eq' => (string) $ids['draft']]]],
        );

        self::assertSame([], $result['items']);
    }

    public function test_total_count_ignores_hidden_posts_when_paginating(): void
    {
        $this->seedPosts();

        $result = $this->resolve(Post\ListResolver::class, $this->context(isAdmin: false), ['pageSize' => 1]);

        self::assertCount(1, $result['items']);
        self::assertSame(3, $result['total_count']);
    }

    public function test_admin_lists_every_post(): void
    {
        $this->seedPosts();

        $result = $this->resolve(
            Post\ListResolver::class,
            $this->context(isAdmin: true),
            [],
            null,
            $this->adminPolicy(),
        );

        self::assertCount(7, $result['items']);
        self::assertSame(7, $result['total_count']);
    }

    public function test_detail_by_id_of_a_draft_fails_like_a_missing_post(): void
    {
        $ids = $this->seedPosts();

        $hidden = $this->notFoundMessage(Post\DetailResolver::class, ['id' => $ids['draft']]);
        $missing = $this->notFoundMessage(Post\DetailResolver::class, ['id' => self::MISSING_ID]);

        self::assertSame(str_replace((string) self::MISSING_ID, (string) $ids['draft'], $missing), $hidden);
    }

    public function test_detail_by_url_key_of_a_draft_fails_like_a_missing_post(): void
    {
        $this->seedPosts();

        $hidden = $this->notFoundMessage(Post\DetailResolver::class, ['url_key' => 'draft']);
        $missing = $this->notFoundMessage(Post\DetailResolver::class, ['url_key' => 'no-such-post']);

        self::assertSame(str_replace('no-such-post', 'draft', $missing), $hidden);
    }

    public function test_detail_by_id_of_a_post_on_another_store_is_hidden(): void
    {
        $ids = $this->seedPosts();

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->resolve(Post\DetailResolver::class, $this->context(isAdmin: false), ['id' => $ids['pub-store-2']]);
    }

    public function test_detail_returns_a_published_post_visible_on_the_store(): void
    {
        $ids = $this->seedPosts();

        $result = $this->resolve(Post\DetailResolver::class, $this->context(isAdmin: false), ['id' => $ids['pub-store-1']]);

        self::assertSame('pub-store-1', $result['url_key']);
    }

    public function test_admin_reads_a_draft_through_the_detail_query(): void
    {
        $ids = $this->seedPosts();

        $result = $this->resolve(
            Post\DetailResolver::class,
            $this->context(isAdmin: true),
            ['id' => $ids['draft']],
            null,
            $this->adminPolicy(),
        );

        self::assertSame('draft', $result['url_key']);
    }

    public function test_visitor_lists_only_active_categories_visible_on_the_store(): void
    {
        $this->seedCategories();

        $result = $this->resolve(Category\ListResolver::class, $this->context(isAdmin: false));

        self::assertEqualsCanonicalizing(['cat-active', 'cat-all'], $this->urlKeys($result));
    }

    public function test_visitor_cannot_widen_the_category_list_with_an_is_active_filter(): void
    {
        $this->seedCategories();

        $result = $this->resolve(
            Category\ListResolver::class,
            $this->context(isAdmin: false),
            ['filter' => ['is_active' => ['eq' => '0']]],
        );

        self::assertSame([], $result['items']);
    }

    public function test_category_detail_by_id_of_an_inactive_one_is_hidden(): void
    {
        $ids = $this->seedCategories();

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->resolve(Category\DetailResolver::class, $this->context(isAdmin: false), ['id' => $ids['cat-inactive']]);
    }

    public function test_hidden_category_fails_like_a_missing_one_by_id_and_by_url_key(): void
    {
        $ids = $this->seedCategories();

        $hiddenById = $this->notFoundMessage(Category\DetailResolver::class, ['id' => $ids['cat-inactive']]);
        $missingById = $this->notFoundMessage(Category\DetailResolver::class, ['id' => self::MISSING_ID]);
        $hiddenByKey = $this->notFoundMessage(Category\DetailResolver::class, ['url_key' => 'cat-inactive']);
        $missingByKey = $this->notFoundMessage(Category\DetailResolver::class, ['url_key' => 'no-such-cat']);

        self::assertSame(str_replace((string) self::MISSING_ID, (string) $ids['cat-inactive'], $missingById), $hiddenById);
        self::assertSame(str_replace('no-such-cat', 'cat-inactive', $missingByKey), $hiddenByKey);
    }

    public function test_hidden_tag_fails_like_a_missing_one_by_id_and_by_url_key(): void
    {
        $ids = $this->seedTags();

        $hiddenById = $this->notFoundMessage(Tag\DetailResolver::class, ['id' => $ids['tag-inactive']]);
        $missingById = $this->notFoundMessage(Tag\DetailResolver::class, ['id' => self::MISSING_ID]);
        $hiddenByKey = $this->notFoundMessage(Tag\DetailResolver::class, ['url_key' => 'tag-inactive']);
        $missingByKey = $this->notFoundMessage(Tag\DetailResolver::class, ['url_key' => 'no-such-tag']);

        self::assertSame(str_replace((string) self::MISSING_ID, (string) $ids['tag-inactive'], $missingById), $hiddenById);
        self::assertSame(str_replace('no-such-tag', 'tag-inactive', $missingByKey), $hiddenByKey);
    }

    public function test_hidden_author_fails_like_a_missing_one_by_id_and_by_slug(): void
    {
        $ids = $this->seedAuthors();

        $hiddenById = $this->notFoundMessage(Author\DetailResolver::class, ['id' => $ids['inactive-author']]);
        $missingById = $this->notFoundMessage(Author\DetailResolver::class, ['id' => self::MISSING_ID]);
        $hiddenBySlug = $this->notFoundMessage(Author\DetailResolver::class, ['slug' => 'inactive-author']);
        $missingBySlug = $this->notFoundMessage(Author\DetailResolver::class, ['slug' => 'no-such-author']);

        self::assertSame(
            str_replace((string) self::MISSING_ID, (string) $ids['inactive-author'], $missingById),
            $hiddenById
        );
        self::assertSame(str_replace('no-such-author', 'inactive-author', $missingBySlug), $hiddenBySlug);
    }

    public function test_visitor_lists_only_active_tags_visible_on_the_store(): void
    {
        $this->seedTags();

        $result = $this->resolve(Tag\ListResolver::class, $this->context(isAdmin: false));

        self::assertEqualsCanonicalizing(['tag-active', 'tag-all'], $this->urlKeys($result));
    }

    public function test_tag_detail_by_id_of_one_on_another_store_is_hidden(): void
    {
        $ids = $this->seedTags();

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->resolve(Tag\DetailResolver::class, $this->context(isAdmin: false), ['id' => $ids['tag-store-2']]);
    }

    public function test_visitor_lists_only_active_authors(): void
    {
        $this->seedAuthors();

        $result = $this->resolve(Author\ListResolver::class, $this->context(isAdmin: false));

        self::assertSame(['active-author'], array_column($result['items'], 'slug'));
    }

    public function test_author_detail_of_an_inactive_one_is_hidden_by_slug(): void
    {
        $this->seedAuthors();

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->resolve(Author\DetailResolver::class, $this->context(isAdmin: false), ['slug' => 'inactive-author']);
    }

    public function test_a_published_post_does_not_leak_its_inactive_category(): void
    {
        $categoryIds = $this->seedCategories();

        $result = $this->resolve(
            Post\CategoriesResolver::class,
            $this->context(isAdmin: false),
            [],
            ['category_ids' => [$categoryIds['cat-active'], $categoryIds['cat-inactive']]],
        );

        self::assertSame(['cat-active'], array_column($result, 'url_key'));
    }

    /**
     * @return array<string, int> post id by url key
     */
    private function seedPosts(): array
    {
        $second = $this->secondStoreId();
        $later = gmdate('Y-m-d H:i:s', time() + 86400);

        return [
            'pub-store-1' => $this->savePost('pub-store-1', BlogPostStatus::Published, [1]),
            'pub-all' => $this->savePost('pub-all', BlogPostStatus::Published, [0]),
            'pub-unassigned' => $this->savePost('pub-unassigned', BlogPostStatus::Published, []),
            'pub-store-2' => $this->savePost('pub-store-2', BlogPostStatus::Published, [$second]),
            'draft' => $this->savePost('draft', BlogPostStatus::Draft, [1]),
            'scheduled' => $this->savePost('scheduled', BlogPostStatus::Scheduled, [1], $later),
            'archived' => $this->savePost('archived', BlogPostStatus::Archived, [1]),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function seedCategories(): array
    {
        $second = $this->secondStoreId();

        return [
            'cat-active' => $this->saveCategory('cat-active', true, [1]),
            'cat-inactive' => $this->saveCategory('cat-inactive', false, [1]),
            'cat-store-2' => $this->saveCategory('cat-store-2', true, [$second]),
            'cat-all' => $this->saveCategory('cat-all', true, [0]),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function seedTags(): array
    {
        $second = $this->secondStoreId();

        return [
            'tag-active' => $this->saveTag('tag-active', true, [1]),
            'tag-inactive' => $this->saveTag('tag-inactive', false, [1]),
            'tag-store-2' => $this->saveTag('tag-store-2', true, [$second]),
            'tag-all' => $this->saveTag('tag-all', true, [0]),
        ];
    }

    /**
     * @return array<string, int> author id by slug
     */
    private function seedAuthors(): array
    {
        $ids = [];
        foreach (['active-author' => true, 'inactive-author' => false] as $slug => $isActive) {
            $author = $this->objectManager->create(AuthorInterfaceFactory::class)->create();
            $author->setName(ucfirst($slug))->setSlug($slug)->setIsActive($isActive);
            $ids[$slug] = (int) $this->objectManager->get(AuthorRepositoryInterface::class)->save($author)->getAuthorId();
        }

        return $ids;
    }

    /**
     * @param int[] $storeIds
     */
    private function savePost(string $urlKey, BlogPostStatus $status, array $storeIds, ?string $publishDate = null): int
    {
        $post = $this->objectManager->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)->setUrlKey($urlKey)->setStatus($status->value)->setStoreIds($storeIds);
        if ($publishDate !== null) {
            $post->setPublishDate($publishDate);
        }

        return (int) $this->objectManager->get(PostRepositoryInterface::class)->save($post)->getPostId();
    }

    /**
     * @param int[] $storeIds
     */
    private function saveCategory(string $urlKey, bool $isActive, array $storeIds): int
    {
        $category = $this->objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($isActive)->setStoreIds($storeIds);

        return (int) $this->objectManager->get(CategoryRepositoryInterface::class)->save($category)->getCategoryId();
    }

    /**
     * @param int[] $storeIds
     */
    private function saveTag(string $urlKey, bool $isActive, array $storeIds): int
    {
        $tag = $this->objectManager->create(TagInterfaceFactory::class)->create();
        $tag->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($isActive)->setStoreIds($storeIds);

        return (int) $this->objectManager->get(TagRepositoryInterface::class)->save($tag)->getTagId();
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed>|null $value
     *
     * @return array<string, mixed>|null
     */
    private function resolve(
        string $resolverClass,
        ContextInterface $context,
        array $args = [],
        ?array $value = null,
        ?ReadPolicy $policy = null,
    ): ?array {
        $resolver = $this->objectManager->create($resolverClass, $policy === null ? [] : ['readPolicy' => $policy]);

        return $resolver->resolve(
            $this->createMock(Field::class),
            $context,
            $this->createMock(ResolveInfo::class),
            $value,
            $args,
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    private function notFoundMessage(string $resolverClass, array $args): string
    {
        try {
            $this->resolve($resolverClass, $this->context(isAdmin: false), $args);
        } catch (GraphQlNoSuchEntityException $e) {
            return $e->getMessage();
        }

        self::fail('Expected a not-found error.');
    }

    /**
     * @param array<string, mixed>|null $result
     *
     * @return string[]
     */
    private function urlKeys(?array $result): array
    {
        return array_column($result['items'] ?? [], 'url_key');
    }

    private function adminPolicy(): ReadPolicy
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        return $this->objectManager->create(
            ReadPolicy::class,
            ['adminAuthorization' => new AdminAuthorization($authorization)],
        );
    }

    private function context(bool $isAdmin): ContextInterface
    {
        return new class ($isAdmin) implements ContextInterface {
            public function __construct(private readonly bool $isAdmin)
            {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->isAdmin) {
                    public function __construct(private readonly bool $isAdmin)
                    {
                    }

                    public function getIsAdmin(): bool
                    {
                        return $this->isAdmin;
                    }
                };
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\Resolver;

use GraphQL\Language\AST\OperationDefinitionNode;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
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
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Category;
use MageOS\Blog\Model\Resolver\Post;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use MageOS\Blog\Model\Resolver\Tag;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * The relation filters of the post, category and tag lists (category, tag, author, store, parent)
 * must work against the database, accept both spellings of a key, and never let a visitor read or
 * detect an entity the storefront hides.
 *
 * NOTE: written without a Magento install at hand, not executed when it was authored.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class FilterKeysTest extends TestCase
{
    use SecondStoreTrait;

    private const MISSING_ID = 987654321;

    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach ($this->objectManager->get(StoreManagerInterface::class)->getStores() as $store) {
            $config->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE, $store->getCode());
        }
    }

    public function test_posts_filter_by_category_id_and_by_category(): void
    {
        $seed = $this->seed();

        foreach (['category_id', 'category'] as $key) {
            $result = $this->listPosts([$key => ['eq' => (string) $seed['cat']]]);
            self::assertEqualsCanonicalizing(['in-cat', 'in-cat-and-tag'], $this->urlKeys($result), $key);
            self::assertSame(2, $result['total_count'], $key);
        }
    }

    public function test_posts_filter_by_tag_id_and_by_tag(): void
    {
        $seed = $this->seed();

        foreach (['tag_id', 'tag'] as $key) {
            $result = $this->listPosts([$key => ['eq' => (string) $seed['tag']]]);
            self::assertEqualsCanonicalizing(['in-tag', 'in-cat-and-tag'], $this->urlKeys($result), $key);
        }
    }

    public function test_a_post_in_two_matching_categories_is_listed_and_counted_once(): void
    {
        $seed = $this->seed();

        $result = $this->listPosts(['category' => ['in' => [(string) $seed['cat'], (string) $seed['cat2']]]]);

        self::assertSame(1, array_count_values($this->urlKeys($result))['in-cat-and-tag']);
        self::assertSame(\count($result['items']), $result['total_count']);
    }

    public function test_posts_filter_by_author_id_and_by_author(): void
    {
        $seed = $this->seed();

        foreach (['author_id', 'author'] as $key) {
            $result = $this->listPosts([$key => ['eq' => (string) $seed['author']]]);
            self::assertSame(['by-author'], $this->urlKeys($result), $key);
        }
    }

    public function test_posts_filter_by_store_id_and_by_store(): void
    {
        $second = $this->secondStoreId();
        $this->seed();

        foreach (['store_id', 'store'] as $key) {
            $result = $this->listPosts([$key => ['eq' => (string) $second]], $this->context(isAdmin: true), $this->adminPolicy());
            self::assertContains('only-store-2', $this->urlKeys($result), $key);
            self::assertNotContains('in-cat', $this->urlKeys($result), $key);
        }
    }

    public function test_both_spellings_are_rejected(): void
    {
        $this->expectException(GraphQlInputException::class);

        $this->listPosts(['category' => ['eq' => '1'], 'category_id' => ['eq' => '1']]);
    }

    public function test_categories_and_tags_filter_by_store_and_store_id(): void
    {
        $second = $this->secondStoreId();
        $this->seed();

        foreach (['store_id', 'store'] as $key) {
            $cats = $this->resolve(Category\ListResolver::class, $this->context(isAdmin: true), ['filter' => [$key => ['eq' => (string) $second]]], $this->adminPolicy());
            self::assertSame(['cat-store-2'], $this->urlKeys($cats), $key);
            $tags = $this->resolve(Tag\ListResolver::class, $this->context(isAdmin: true), ['filter' => [$key => ['eq' => (string) $second]]], $this->adminPolicy());
            self::assertSame(['tag-store-2'], $this->urlKeys($tags), $key);
        }
    }

    public function test_categories_filter_by_parent_id_and_by_parent(): void
    {
        $seed = $this->seed();

        foreach (['parent_id', 'parent'] as $key) {
            $result = $this->resolve(Category\ListResolver::class, $this->context(isAdmin: false), ['filter' => [$key => ['eq' => (string) $seed['cat']]]]);
            self::assertSame(['child-of-cat'], $this->urlKeys($result), $key);
        }
    }

    public function test_visitor_filtering_by_a_hidden_category_gets_the_same_empty_result_as_a_missing_id(): void
    {
        $seed = $this->seed();

        $hidden = $this->listPosts(['category' => ['eq' => (string) $seed['cat-hidden']]]);
        $missing = $this->listPosts(['category' => ['eq' => (string) self::MISSING_ID]]);

        self::assertSame([], $hidden['items']);
        self::assertSame($missing, $hidden);
    }

    public function test_visitor_filtering_by_a_hidden_tag_gets_the_same_empty_result_as_a_missing_id(): void
    {
        $seed = $this->seed();

        $hidden = $this->listPosts(['tag_id' => ['eq' => (string) $seed['tag-hidden']]]);

        self::assertSame($this->listPosts(['tag_id' => ['eq' => (string) self::MISSING_ID]]), $hidden);
        self::assertSame([], $hidden['items']);
    }

    public function test_visitor_filtering_by_a_hidden_author_gets_the_same_empty_result_as_a_missing_id(): void
    {
        $seed = $this->seed();

        $hidden = $this->listPosts(['author' => ['eq' => (string) $seed['author-hidden']]]);

        self::assertSame($this->listPosts(['author' => ['eq' => (string) self::MISSING_ID]]), $hidden);
        self::assertSame([], $hidden['items']);
    }

    public function test_a_hidden_id_in_an_in_list_does_not_widen_or_leak(): void
    {
        $seed = $this->seed();

        $result = $this->listPosts(['category' => ['in' => [(string) $seed['cat-hidden'], (string) $seed['cat']]]]);

        self::assertEqualsCanonicalizing(['in-cat', 'in-cat-and-tag'], $this->urlKeys($result));
    }

    public function test_visitor_filtering_by_a_hidden_parent_category_gets_the_same_result_as_a_missing_id(): void
    {
        $seed = $this->seed();

        $hidden = $this->resolve(Category\ListResolver::class, $this->context(isAdmin: false), ['filter' => ['parent' => ['eq' => (string) $seed['cat-hidden']]]]);
        $missing = $this->resolve(Category\ListResolver::class, $this->context(isAdmin: false), ['filter' => ['parent' => ['eq' => (string) self::MISSING_ID]]]);

        self::assertSame([], $hidden['items']);
        self::assertSame($missing, $hidden);
    }

    public function test_visitor_cannot_read_a_draft_post_through_a_category_filter(): void
    {
        $seed = $this->seed();

        $result = $this->listPosts([
            'status' => ['eq' => (string) BlogPostStatus::Draft->value],
            'category' => ['eq' => (string) $seed['cat']],
        ]);

        self::assertSame([], $result['items']);
    }

    public function test_admin_filters_by_a_hidden_category(): void
    {
        $seed = $this->seed();

        $result = $this->listPosts(
            ['category' => ['eq' => (string) $seed['cat-hidden']]],
            $this->context(isAdmin: true),
            $this->adminPolicy(),
        );

        self::assertSame(['in-hidden-cat'], $this->urlKeys($result));
    }

    /**
     * @return array<string, int>
     */
    private function seed(): array
    {
        $second = $this->secondStoreId();
        $ids = [];
        $ids['cat'] = $this->saveCategory('cat', true, [0]);
        $ids['cat2'] = $this->saveCategory('cat2', true, [0]);
        $ids['cat-hidden'] = $this->saveCategory('cat-hidden', false, [0]);
        $this->saveCategory('cat-store-2', true, [$second]);
        $this->saveCategory('child-of-cat', true, [0], $ids['cat']);
        $this->saveCategory('child-of-hidden', true, [0], $ids['cat-hidden']);
        $ids['tag'] = $this->saveTag('tag', true, [0]);
        $ids['tag-hidden'] = $this->saveTag('tag-hidden', false, [0]);
        $this->saveTag('tag-store-2', true, [$second]);
        $author = $this->objectManager->create(AuthorInterfaceFactory::class)->create();
        $author->setName('Visible')->setSlug('visible')->setIsActive(true);
        $ids['author'] = (int) $this->objectManager->get(AuthorRepositoryInterface::class)->save($author)->getAuthorId();
        $hidden = $this->objectManager->create(AuthorInterfaceFactory::class)->create();
        $hidden->setName('Hidden')->setSlug('hidden')->setIsActive(false);
        $ids['author-hidden'] = (int) $this->objectManager->get(AuthorRepositoryInterface::class)->save($hidden)->getAuthorId();

        $this->savePost('in-cat', [$ids['cat']], []);
        $this->savePost('in-tag', [], [$ids['tag']]);
        $this->savePost('in-cat-and-tag', [$ids['cat'], $ids['cat2']], [$ids['tag']]);
        $this->savePost('by-author', [], [], [0], $ids['author']);
        $this->savePost('in-hidden-cat', [$ids['cat-hidden']], []);
        $this->savePost('only-store-2', [], [], [$second]);
        $this->savePost('draft-in-cat', [$ids['cat']], [], [0], null, BlogPostStatus::Draft);

        return $ids;
    }

    /**
     * @param int[] $categoryIds
     * @param int[] $tagIds
     * @param int[] $storeIds
     */
    private function savePost(
        string $urlKey,
        array $categoryIds,
        array $tagIds,
        array $storeIds = [0],
        ?int $authorId = null,
        BlogPostStatus $status = BlogPostStatus::Published,
    ): void {
        $post = $this->objectManager->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)->setUrlKey($urlKey)->setStatus($status->value)->setStoreIds($storeIds)
            ->setCategoryIds($categoryIds)->setTagIds($tagIds)->setAuthorId($authorId);
        $this->objectManager->get(PostRepositoryInterface::class)->save($post);
    }

    /**
     * @param int[] $storeIds
     */
    private function saveCategory(string $urlKey, bool $isActive, array $storeIds, int $parentId = 0): int
    {
        $category = $this->objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($isActive)->setStoreIds($storeIds)->setParentId($parentId);

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
     * @param array<string, mixed> $filter
     *
     * @return array<string, mixed>
     */
    private function listPosts(array $filter, ?ContextInterface $context = null, ?ReadPolicy $policy = null): array
    {
        return $this->resolve(Post\ListResolver::class, $context ?? $this->context(isAdmin: false), ['filter' => $filter], $policy);
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function resolve(string $resolverClass, ContextInterface $context, array $args, ?ReadPolicy $policy = null): array
    {
        $arguments = [];
        if ($policy !== null) {
            // The guard must judge the caller with the same policy as the resolver.
            $arguments = [
                'readPolicy' => $policy,
                'relationFilterGuard' => $this->objectManager->create(RelationFilterGuard::class, ['readPolicy' => $policy]),
            ];
        }
        $resolver = $this->objectManager->create($resolverClass, $arguments);
        $info = $this->createMock(ResolveInfo::class);
        $info->operation = new OperationDefinitionNode(['operation' => 'query']);

        return $resolver->resolve($this->createMock(Field::class), $context, $info, null, $args);
    }

    /**
     * @param array<string, mixed> $result
     *
     * @return string[]
     */
    private function urlKeys(array $result): array
    {
        return array_column($result['items'], 'url_key');
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
        return new class ($isAdmin, $this->objectManager->get(StoreManagerInterface::class)) implements ContextInterface {
            public function __construct(
                private readonly bool $isAdmin,
                private readonly StoreManagerInterface $storeManager,
            ) {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->isAdmin, $this->storeManager) {
                    public function __construct(
                        private readonly bool $isAdmin,
                        private readonly StoreManagerInterface $storeManager,
                    ) {
                    }

                    public function getIsAdmin(): bool
                    {
                        return $this->isAdmin;
                    }

                    public function getStore(): StoreInterface
                    {
                        return $this->storeManager->getStore();
                    }
                };
            }
        };
    }
}

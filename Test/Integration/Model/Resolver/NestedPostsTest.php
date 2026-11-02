<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\Resolver;

use GraphQL\Language\AST\OperationDefinitionNode;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
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
use MageOS\Blog\Model\Resolver\Author;
use MageOS\Blog\Model\Resolver\Category;
use MageOS\Blog\Model\Resolver\Post;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\Tag;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * The `posts` field of a category, tag and author lists what the storefront shows for that parent,
 * never a draft or another store view's post, and never more than the parent constraint allows.
 *
 * NOT RUN by the author: written without a Magento install, mirrors ReadVisibilityTest.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class NestedPostsTest extends TestCase
{
    use SecondStoreTrait;

    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->setBlogEnabled(true);
    }

    public function test_visitor_sees_only_published_posts_of_the_current_store_under_each_parent(): void
    {
        $ids = $this->seed();

        foreach ($this->parents($ids) as $label => [$class, $parentId]) {
            $result = $this->resolve($class, $this->context(false), ['id' => $parentId]);

            self::assertEqualsCanonicalizing(['pub-store-1', 'pub-all'], array_column($result['items'], 'url_key'), $label);
            self::assertSame(2, $result['total_count'], $label);
        }
    }

    public function test_admin_sees_every_post_of_the_parent(): void
    {
        $ids = $this->seed();

        foreach ($this->parents($ids) as $label => [$class, $parentId]) {
            $result = $this->resolve($class, $this->context(true), ['id' => $parentId], $this->adminPolicy());

            self::assertSame(4, $result['total_count'], $label);
            self::assertContains('draft', array_column($result['items'], 'url_key'), $label);
        }
    }

    public function test_a_client_filter_for_another_parent_cannot_widen_the_result(): void
    {
        $ids = $this->seed();

        $result = $this->resolve(
            Category\PostsResolver::class,
            $this->context(false),
            ['id' => $ids['cat']],
            null,
            ['filter' => ['category' => ['eq' => (string) $ids['other-cat']]]]
        );

        self::assertSame([], $result['items']);
        self::assertSame(0, $result['total_count']);
    }

    public function test_a_visitor_filtering_the_nested_list_by_a_hidden_status_gets_nothing(): void
    {
        $ids = $this->seed();

        $result = $this->resolve(
            Tag\PostsResolver::class,
            $this->context(false),
            ['id' => $ids['tag']],
            null,
            ['filter' => ['status' => ['eq' => (string) BlogPostStatus::Draft->value]]]
        );

        self::assertSame(0, $result['total_count']);
    }

    public function test_nothing_is_listed_for_a_visitor_while_the_blog_is_disabled(): void
    {
        $ids = $this->seed();
        $this->setBlogEnabled(false);

        $result = $this->resolve(Author\PostsResolver::class, $this->context(false), ['id' => $ids['author']]);

        self::assertSame(0, $result['total_count']);
    }

    public function test_the_nested_page_size_is_capped(): void
    {
        $ids = $this->seed();

        $result = $this->resolve(Category\PostsResolver::class, $this->context(false), ['id' => $ids['cat']], null, ['pageSize' => 100000]);

        self::assertSame(100, $result['page_info']['page_size']);
    }

    /**
     * @param array<string, int> $ids
     * @return array<string, array{class-string, int}>
     */
    private function parents(array $ids): array
    {
        return [
            'category' => [Category\PostsResolver::class, $ids['cat']],
            'tag' => [Tag\PostsResolver::class, $ids['tag']],
            'author' => [Author\PostsResolver::class, $ids['author']],
        ];
    }

    /**
     * Four posts under the parents (two readable by a visitor), plus one post of another category.
     *
     * @return array<string, int>
     */
    private function seed(): array
    {
        $second = $this->secondStoreId();
        $cat = $this->saveCategory('cat');
        $otherCat = $this->saveCategory('other-cat');
        $tag = $this->saveTag('tag-a');
        $author = $this->saveAuthor('writer');

        $this->savePost('pub-store-1', BlogPostStatus::Published, [1], $cat, $tag, $author);
        $this->savePost('pub-all', BlogPostStatus::Published, [0], $cat, $tag, $author);
        $this->savePost('pub-store-2', BlogPostStatus::Published, [$second], $cat, $tag, $author);
        $this->savePost('draft', BlogPostStatus::Draft, [1], $cat, $tag, $author);
        $this->savePost('elsewhere', BlogPostStatus::Published, [1], $otherCat, null, null);

        return ['cat' => $cat, 'other-cat' => $otherCat, 'tag' => $tag, 'author' => $author];
    }

    /**
     * @param int[] $storeIds
     */
    private function savePost(string $urlKey, BlogPostStatus $status, array $storeIds, int $categoryId, ?int $tagId, ?int $authorId): void
    {
        $post = $this->objectManager->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)->setUrlKey($urlKey)->setStatus($status->value)->setStoreIds($storeIds)
            ->setCategoryIds([$categoryId])->setTagIds($tagId === null ? [] : [$tagId]);
        if ($authorId !== null) {
            $post->setAuthorId($authorId);
        }
        $this->objectManager->get(PostRepositoryInterface::class)->save($post);
    }

    private function saveCategory(string $urlKey): int
    {
        $category = $this->objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive(true)->setStoreIds([0]);

        return (int) $this->objectManager->get(CategoryRepositoryInterface::class)->save($category)->getCategoryId();
    }

    private function saveTag(string $urlKey): int
    {
        $tag = $this->objectManager->create(TagInterfaceFactory::class)->create();
        $tag->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive(true)->setStoreIds([0]);

        return (int) $this->objectManager->get(TagRepositoryInterface::class)->save($tag)->getTagId();
    }

    private function saveAuthor(string $slug): int
    {
        $author = $this->objectManager->create(AuthorInterfaceFactory::class)->create();
        $author->setName($slug)->setSlug($slug)->setIsActive(true);

        return (int) $this->objectManager->get(AuthorRepositoryInterface::class)->save($author)->getAuthorId();
    }

    private function setBlogEnabled(bool $enabled): void
    {
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach ($this->objectManager->get(StoreManagerInterface::class)->getStores() as $store) {
            $config->setValue(Config::XML_PATH_ENABLED, $enabled ? '1' : '0', ScopeInterface::SCOPE_STORE, $store->getCode());
        }
    }

    /**
     * @param array<string, mixed> $parent the mapped parent, only its id is read
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function resolve(string $class, ContextInterface $context, array $parent, ?ReadPolicy $policy = null, array $args = []): array
    {
        $info = $this->createMock(ResolveInfo::class);
        $info->operation = new OperationDefinitionNode(['operation' => 'query']);
        $list = $this->objectManager->create(Post\ListResolver::class, $policy === null ? [] : ['readPolicy' => $policy]);

        return $this->objectManager->create($class, ['postList' => $list])
            ->resolve($this->createMock(Field::class), $context, $info, $parent, $args);
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

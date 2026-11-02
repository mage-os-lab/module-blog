<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Integration\Model\Resolver;

use GraphQL\Language\AST\OperationDefinitionNode;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Resolver\Category;
use MageOS\Blog\Model\Resolver\Post;
use MageOS\Blog\Model\Resolver\Tag;
use MageOS\Blog\Test\Integration\SecondStoreTrait;
use PHPUnit\Framework\TestCase;

/**
 * List items must carry their links like the detail query does, one page at a time, without
 * letting a visitor read or detect anything the storefront hides.
 *
 * NOT RUN by the author: written without a Magento install, mirrors ReadVisibilityTest.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 * @magentoDataFixture Magento/Store/_files/store.php
 */
final class ListLinksTest extends TestCase
{
    use SecondStoreTrait;

    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach ($this->objectManager->get(StoreManagerInterface::class)->getStores() as $store) {
            $config->setValue(Config::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE, $store->getCode());
        }
    }

    public function test_list_items_carry_their_category_tag_and_store_ids(): void
    {
        $fixture = $this->seed();

        $result = $this->resolve(Post\ListResolver::class, ['pageSize' => 10]);
        $byKey = array_column($result['items'], null, 'url_key');

        self::assertEqualsCanonicalizing([$fixture['news'], $fixture['tips']], $byKey['first']['category_ids']);
        self::assertSame([$fixture['news']], $byKey['second']['category_ids']);
        self::assertSame([$fixture['php']], $byKey['first']['tag_ids']);
        self::assertSame([0], $byKey['first']['store_ids']);
    }

    public function test_nested_fields_of_listed_posts_resolve(): void
    {
        $fixture = $this->seed();

        $result = $this->resolve(Post\ListResolver::class, ['pageSize' => 10]);
        $first = array_column($result['items'], null, 'url_key')['first'];
        $categories = $this->resolve(Post\CategoriesResolver::class, [], $first);

        self::assertEqualsCanonicalizing([$fixture['news'], $fixture['tips']], array_column($categories, 'id'));
    }

    public function test_visitor_only_gets_the_readable_links_of_listed_posts(): void
    {
        $fixture = $this->seed();

        $result = $this->resolve(Post\ListResolver::class, ['pageSize' => 10]);
        $byKey = array_column($result['items'], null, 'url_key');

        // The list item names the inactive category's id (it is a link, not an entity read) ...
        self::assertContains($fixture['hidden-cat'], $byKey['with-hidden-category']['category_ids']);
        // ... but the nested field a visitor can actually query leaves it out.
        $nested = $this->resolve(Post\CategoriesResolver::class, [], $byKey['with-hidden-category']);
        self::assertSame([$fixture['news']], array_column($nested, 'id'));
    }

    public function test_visitor_does_not_get_hidden_posts_hydrated_or_listed(): void
    {
        $this->seed();

        $result = $this->resolve(Post\ListResolver::class, ['pageSize' => 10]);

        self::assertNotContains('draft', array_column($result['items'], 'url_key'));
        self::assertSame(3, $result['total_count']);
    }

    public function test_category_and_tag_lists_carry_store_ids_and_still_hide_inactive_ones(): void
    {
        $fixture = $this->seed();

        $categories = $this->resolve(Category\ListResolver::class);
        $tags = $this->resolve(Tag\ListResolver::class);

        self::assertNotContains($fixture['hidden-cat'], array_column($categories['items'], 'id'));
        self::assertSame([0], array_column($categories['items'], null, 'id')[$fixture['news']]['store_ids']);
        self::assertSame([0], array_column($tags['items'], null, 'id')[$fixture['php']]['store_ids']);
    }

    public function test_page_size_zero_is_rejected(): void
    {
        $this->expectException(GraphQlInputException::class);

        $this->resolve(Post\ListResolver::class, ['pageSize' => 0]);
    }

    public function test_page_size_above_the_cap_is_clamped(): void
    {
        $this->seed();

        $result = $this->resolve(Post\ListResolver::class, ['pageSize' => 100000]);

        self::assertSame(100, $result['page_info']['page_size']);
    }

    /**
     * @return array<string, int|string>
     */
    private function seed(): array
    {
        $news = $this->saveCategory('news', true);
        $tips = $this->saveCategory('tips', true);
        $hiddenCat = $this->saveCategory('hidden-cat', false);
        $php = $this->saveTag('php');

        $this->savePost('first', BlogPostStatus::Published, [$news, $tips], [$php]);
        $this->savePost('second', BlogPostStatus::Published, [$news], []);
        $this->savePost('with-hidden-category', BlogPostStatus::Published, [$news, $hiddenCat], []);
        $this->savePost('draft', BlogPostStatus::Draft, [$news], []);

        return ['news' => $news, 'tips' => $tips, 'hidden-cat' => $hiddenCat, 'php' => $php];
    }

    /**
     * @param int[] $categoryIds
     * @param int[] $tagIds
     */
    private function savePost(string $urlKey, BlogPostStatus $status, array $categoryIds, array $tagIds): void
    {
        $post = $this->objectManager->create(PostInterfaceFactory::class)->create();
        $post->setTitle($urlKey)->setUrlKey($urlKey)->setStatus($status->value)->setStoreIds([0])
            ->setCategoryIds($categoryIds)->setTagIds($tagIds);
        $this->objectManager->get(PostRepositoryInterface::class)->save($post);
    }

    private function saveCategory(string $urlKey, bool $isActive): int
    {
        $category = $this->objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($isActive)->setStoreIds([0]);

        return (int) $this->objectManager->get(CategoryRepositoryInterface::class)->save($category)->getCategoryId();
    }

    private function saveTag(string $urlKey): int
    {
        $tag = $this->objectManager->create(TagInterfaceFactory::class)->create();
        $tag->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive(true)->setStoreIds([0]);

        return (int) $this->objectManager->get(TagRepositoryInterface::class)->save($tag)->getTagId();
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed>|null $value
     *
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function resolve(string $resolverClass, array $args = [], ?array $value = null): ?array
    {
        $info = $this->createMock(ResolveInfo::class);
        $info->operation = new OperationDefinitionNode(['operation' => 'query']);

        return $this->objectManager->create($resolverClass)
            ->resolve($this->createMock(Field::class), $this->visitorContext(), $info, $value, $args);
    }

    private function visitorContext(): ContextInterface
    {
        return new class ($this->objectManager->get(StoreManagerInterface::class)) implements ContextInterface {
            public function __construct(private readonly StoreManagerInterface $storeManager)
            {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->storeManager) {
                    public function __construct(private readonly StoreManagerInterface $storeManager)
                    {
                    }

                    public function getIsAdmin(): bool
                    {
                        return false;
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

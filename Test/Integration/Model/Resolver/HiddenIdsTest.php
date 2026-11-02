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
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\Category;
use MageOS\Blog\Model\Resolver\Post;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use PHPUnit\Framework\TestCase;

/**
 * The deprecated raw id fields (author_id, category_ids, tag_ids, parent_id) and BlogCategory.parent
 * never name an entity the caller may not read.
 *
 * NOT RUN by the author: written without a Magento install, mirrors NestedPostsTest.
 *
 * @magentoAppArea graphql
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
final class HiddenIdsTest extends TestCase
{
    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->setBlogEnabled(true);
    }

    public function test_a_visitor_gets_only_the_ids_of_readable_entities(): void
    {
        $ids = $this->seed();
        $post = ['category_ids' => [$ids['cat'], $ids['hidden-cat']], 'tag_ids' => [$ids['tag'], $ids['hidden-tag']], 'author_id' => $ids['hidden-author']];

        self::assertSame([$ids['cat']], $this->resolve(Post\CategoryIdsResolver::class, $this->context(false), $post));
        self::assertSame([$ids['tag']], $this->resolve(Post\TagIdsResolver::class, $this->context(false), $post));
        self::assertNull($this->resolve(Post\AuthorIdResolver::class, $this->context(false), $post));
    }

    public function test_a_visitor_gets_no_parent_when_the_parent_is_hidden(): void
    {
        $ids = $this->seed();
        $child = ['parent_id' => $ids['hidden-cat']];

        self::assertNull($this->resolve(Category\ParentIdResolver::class, $this->context(false), $child));
        self::assertNull($this->resolve(Category\ParentResolver::class, $this->context(false), $child));
        self::assertSame($ids['cat'], $this->resolve(Category\ParentIdResolver::class, $this->context(false), ['parent_id' => $ids['cat']]));
        self::assertSame($ids['cat'], $this->resolve(Category\ParentResolver::class, $this->context(false), ['parent_id' => $ids['cat']])['id']);
    }

    public function test_a_visitor_gets_nothing_while_the_blog_is_disabled(): void
    {
        $ids = $this->seed();
        $this->setBlogEnabled(false);

        self::assertSame([], $this->resolve(Post\CategoryIdsResolver::class, $this->context(false), ['category_ids' => [$ids['cat']]]));
        self::assertNull($this->resolve(Category\ParentIdResolver::class, $this->context(false), ['parent_id' => $ids['cat']]));
    }

    public function test_an_admin_with_the_acl_resources_gets_every_id(): void
    {
        $ids = $this->seed();
        $post = ['category_ids' => [$ids['cat'], $ids['hidden-cat']], 'tag_ids' => [$ids['tag'], $ids['hidden-tag']], 'author_id' => $ids['hidden-author']];
        $policy = $this->adminPolicy();

        self::assertSame([$ids['cat'], $ids['hidden-cat']], $this->resolve(Post\CategoryIdsResolver::class, $this->context(true), $post, $policy));
        self::assertSame([$ids['tag'], $ids['hidden-tag']], $this->resolve(Post\TagIdsResolver::class, $this->context(true), $post, $policy));
        self::assertSame($ids['hidden-author'], $this->resolve(Post\AuthorIdResolver::class, $this->context(true), $post, $policy));
        self::assertSame($ids['hidden-cat'], $this->resolve(Category\ParentIdResolver::class, $this->context(true), ['parent_id' => $ids['hidden-cat']], $policy));
    }

    /**
     * @return array<string, int>
     */
    private function seed(): array
    {
        return [
            'cat' => $this->saveCategory('cat', true),
            'hidden-cat' => $this->saveCategory('hidden-cat', false),
            'tag' => $this->saveTag('tag-a', true),
            'hidden-tag' => $this->saveTag('hidden-tag', false),
            'hidden-author' => $this->saveAuthor('hidden-author', false),
        ];
    }

    private function saveCategory(string $urlKey, bool $active): int
    {
        $category = $this->objectManager->create(CategoryInterfaceFactory::class)->create();
        $category->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($active)->setStoreIds([0]);

        return (int) $this->objectManager->get(CategoryRepositoryInterface::class)->save($category)->getCategoryId();
    }

    private function saveTag(string $urlKey, bool $active): int
    {
        $tag = $this->objectManager->create(TagInterfaceFactory::class)->create();
        $tag->setTitle($urlKey)->setUrlKey($urlKey)->setIsActive($active)->setStoreIds([0]);

        return (int) $this->objectManager->get(TagRepositoryInterface::class)->save($tag)->getTagId();
    }

    private function saveAuthor(string $slug, bool $active): int
    {
        $author = $this->objectManager->create(AuthorInterfaceFactory::class)->create();
        $author->setName($slug)->setSlug($slug)->setIsActive($active);

        return (int) $this->objectManager->get(AuthorRepositoryInterface::class)->save($author)->getAuthorId();
    }

    /**
     * @param array<string, mixed> $value the mapped parent entity
     *
     * @return mixed
     */
    private function infoStub(): ResolveInfo
    {
        // Plugins on every resolver (ReCaptcha's validator) read the operation type from the info.
        $info = $this->createMock(ResolveInfo::class);
        $info->operation = new OperationDefinitionNode(['operation' => 'query']);

        return $info;
    }

    private function resolve(string $class, ContextInterface $context, array $value, ?ReadPolicy $policy = null)
    {
        return $this->objectManager->create($class, $policy === null ? [] : ['readPolicy' => $policy])
            ->resolve($this->createMock(Field::class), $context, $this->infoStub(), $value);
    }

    private function setBlogEnabled(bool $enabled): void
    {
        $config = $this->objectManager->get(MutableScopeConfigInterface::class);
        foreach ($this->objectManager->get(StoreManagerInterface::class)->getStores() as $store) {
            $config->setValue(Config::XML_PATH_ENABLED, $enabled ? '1' : '0', ScopeInterface::SCOPE_STORE, $store->getCode());
        }
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

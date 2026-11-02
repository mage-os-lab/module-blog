<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\StoreVisibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ReadPolicyTest extends TestCase
{
    private const STORE_ID = 2;

    private ContextInterface $context;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ContextInterface::class);
    }

    #[Test]
    public function a_visitor_lists_only_published_posts_of_the_current_store(): void
    {
        $policy = $this->policy(isAdmin: false);

        self::assertSame(
            ['status' => BlogPostStatus::Published->value, 'store_ids' => self::STORE_ID],
            $policy->postListFilters($this->context)
        );
    }

    #[Test]
    public function a_visitor_lists_only_active_categories_of_the_current_store(): void
    {
        self::assertSame(
            ['is_active' => 1, 'store_ids' => self::STORE_ID],
            $this->policy(isAdmin: false)->categoryListFilters($this->context)
        );
    }

    #[Test]
    public function a_visitor_lists_only_active_tags_of_the_current_store(): void
    {
        self::assertSame(
            ['is_active' => 1, 'store_ids' => self::STORE_ID],
            $this->policy(isAdmin: false)->tagListFilters($this->context)
        );
    }

    #[Test]
    public function a_visitor_lists_only_active_authors(): void
    {
        self::assertSame(
            ['is_active' => 1],
            $this->policy(isAdmin: false)->authorListFilters($this->context)
        );
    }

    #[Test]
    public function an_authorized_admin_lists_without_restrictions(): void
    {
        $policy = $this->policy(isAdmin: true);

        self::assertSame([], $policy->postListFilters($this->context));
        self::assertSame([], $policy->categoryListFilters($this->context));
        self::assertSame([], $policy->tagListFilters($this->context));
        self::assertSame([], $policy->authorListFilters($this->context));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function aclResources(): array
    {
        return [
            'posts' => ['postListFilters', 'MageOS_Blog::post'],
            'categories' => ['categoryListFilters', 'MageOS_Blog::category'],
            'tags' => ['tagListFilters', 'MageOS_Blog::tag'],
            'authors' => ['authorListFilters', 'MageOS_Blog::author'],
        ];
    }

    #[Test]
    #[DataProvider('aclResources')]
    public function listing_asks_the_acl_for_the_entity_s_own_resource(string $method, string $resource): void
    {
        $adminAuthorization = $this->createMock(AdminAuthorization::class);
        $adminAuthorization->expects(self::once())
            ->method('isAuthorized')
            ->with($this->context, $resource)
            ->willReturn(false);

        $this->policy(adminAuthorization: $adminAuthorization)->{$method}($this->context);
    }

    /**
     * @return array<string, array{string, class-string, string}>
     */
    public static function readAclResources(): array
    {
        return [
            'post' => ['canReadPost', PostInterface::class, 'MageOS_Blog::post'],
            'category' => ['canReadCategory', CategoryInterface::class, 'MageOS_Blog::category'],
            'tag' => ['canReadTag', TagInterface::class, 'MageOS_Blog::tag'],
            'author' => ['canReadAuthor', AuthorInterface::class, 'MageOS_Blog::author'],
        ];
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('readAclResources')]
    public function reading_a_hidden_entity_asks_the_acl_for_its_own_resource(
        string $method,
        string $entityClass,
        string $resource,
    ): void {
        // An unconfigured mock is a draft / inactive entity, so the public rule says no and the ACL decides.
        $adminAuthorization = $this->createMock(AdminAuthorization::class);
        $adminAuthorization->expects(self::once())
            ->method('isAuthorized')
            ->with($this->context, $resource)
            ->willReturn(false);

        $policy = $this->policy(adminAuthorization: $adminAuthorization);

        self::assertFalse($policy->{$method}($this->context, $this->createMock($entityClass)));
    }

    #[Test]
    #[DataProvider('readAclResources')]
    public function reading_a_public_entity_never_touches_the_acl(
        string $method,
        string $entityClass,
        string $resource,
    ): void {
        $entity = $this->createMock($entityClass);
        if ($entity instanceof PostInterface) {
            $entity->method('getStatus')->willReturn(BlogPostStatus::Published->value);
        } else {
            $entity->method('getIsActive')->willReturn(true);
        }
        $adminAuthorization = $this->createMock(AdminAuthorization::class);
        $adminAuthorization->expects(self::never())->method('isAuthorized');

        self::assertTrue($this->policy(adminAuthorization: $adminAuthorization)->{$method}($this->context, $entity));
    }

    /**
     * @return array<string, array{int, int[], bool, bool}>
     */
    public static function postCases(): array
    {
        $published = BlogPostStatus::Published->value;

        return [
            'published on the current store' => [$published, [self::STORE_ID], false, true],
            'published on several stores incl. the current' => [$published, [1, self::STORE_ID], false, true],
            'published on all store views' => [$published, [0], false, true],
            'published and assigned to no store' => [$published, [], false, true],
            'published on another store only' => [$published, [1], false, false],
            'draft' => [BlogPostStatus::Draft->value, [self::STORE_ID], false, false],
            'scheduled' => [BlogPostStatus::Scheduled->value, [self::STORE_ID], false, false],
            'archived' => [BlogPostStatus::Archived->value, [self::STORE_ID], false, false],
            'admin reads a draft' => [BlogPostStatus::Draft->value, [self::STORE_ID], true, true],
            'admin reads a post of another store' => [$published, [1], true, true],
        ];
    }

    /**
     * @param int[] $storeIds
     */
    #[Test]
    #[DataProvider('postCases')]
    public function can_read_post(int $status, array $storeIds, bool $isAdmin, bool $expected): void
    {
        $post = $this->createMock(PostInterface::class);
        $post->method('getStatus')->willReturn($status);
        $post->method('getStoreIds')->willReturn($storeIds);

        self::assertSame($expected, $this->policy($isAdmin)->canReadPost($this->context, $post));
    }

    /**
     * @return array<string, array{bool, int[], bool, bool}>
     */
    public static function categoryAndTagCases(): array
    {
        return [
            'active on the current store' => [true, [self::STORE_ID], false, true],
            'active on all store views' => [true, [0], false, true],
            'active and assigned to no store' => [true, [], false, true],
            'active on another store only' => [true, [1], false, false],
            'inactive' => [false, [self::STORE_ID], false, false],
            'admin reads an inactive one' => [false, [self::STORE_ID], true, true],
            'admin reads one of another store' => [true, [1], true, true],
        ];
    }

    /**
     * @param int[] $storeIds
     */
    #[Test]
    #[DataProvider('categoryAndTagCases')]
    public function can_read_category(bool $isActive, array $storeIds, bool $isAdmin, bool $expected): void
    {
        $category = $this->createMock(CategoryInterface::class);
        $category->method('getIsActive')->willReturn($isActive);
        $category->method('getStoreIds')->willReturn($storeIds);

        self::assertSame($expected, $this->policy($isAdmin)->canReadCategory($this->context, $category));
    }

    /**
     * @param int[] $storeIds
     */
    #[Test]
    #[DataProvider('categoryAndTagCases')]
    public function can_read_tag(bool $isActive, array $storeIds, bool $isAdmin, bool $expected): void
    {
        $tag = $this->createMock(TagInterface::class);
        $tag->method('getIsActive')->willReturn($isActive);
        $tag->method('getStoreIds')->willReturn($storeIds);

        self::assertSame($expected, $this->policy($isAdmin)->canReadTag($this->context, $tag));
    }

    /**
     * @return array<string, array{bool, bool, bool}>
     */
    public static function authorCases(): array
    {
        return [
            'active author' => [true, false, true],
            'inactive author' => [false, false, false],
            'admin reads an inactive author' => [false, true, true],
        ];
    }

    #[Test]
    #[DataProvider('authorCases')]
    public function can_read_author(bool $isActive, bool $isAdmin, bool $expected): void
    {
        $author = $this->createMock(AuthorInterface::class);
        $author->method('getIsActive')->willReturn($isActive);

        self::assertSame($expected, $this->policy($isAdmin)->canReadAuthor($this->context, $author));
    }

    #[Test]
    public function a_visitor_lists_nothing_while_the_blog_is_disabled_on_the_store(): void
    {
        $policy = $this->policy(isAdmin: false, blogEnabled: false);

        // No row has id 0, so the required filter matches nothing.
        self::assertSame(['post_id' => 0], $policy->postListFilters($this->context));
        self::assertSame(['category_id' => 0], $policy->categoryListFilters($this->context));
        self::assertSame(['tag_id' => 0], $policy->tagListFilters($this->context));
        self::assertSame(['author_id' => 0], $policy->authorListFilters($this->context));
    }

    #[Test]
    public function an_authorized_admin_still_lists_while_the_blog_is_disabled(): void
    {
        $policy = $this->policy(isAdmin: true, blogEnabled: false);

        self::assertSame([], $policy->postListFilters($this->context));
        self::assertSame([], $policy->categoryListFilters($this->context));
        self::assertSame([], $policy->tagListFilters($this->context));
        self::assertSame([], $policy->authorListFilters($this->context));
    }

    #[Test]
    #[DataProvider('readAclResources')]
    public function a_visitor_reads_nothing_while_the_blog_is_disabled(
        string $method,
        string $entityClass,
        string $resource,
    ): void {
        $entity = $this->createMock($entityClass);
        if ($entity instanceof PostInterface) {
            $entity->method('getStatus')->willReturn(BlogPostStatus::Published->value);
        } else {
            $entity->method('getIsActive')->willReturn(true);
        }

        self::assertFalse($this->policy(isAdmin: false, blogEnabled: false)->{$method}($this->context, $entity));
    }

    #[Test]
    #[DataProvider('readAclResources')]
    public function an_authorized_admin_still_reads_while_the_blog_is_disabled(
        string $method,
        string $entityClass,
        string $resource,
    ): void {
        $entity = $this->createMock($entityClass);

        self::assertTrue($this->policy(isAdmin: true, blogEnabled: false)->{$method}($this->context, $entity));
    }

    #[Test]
    public function the_author_email_is_for_admins_with_the_author_resource_only(): void
    {
        $adminAuthorization = $this->createMock(AdminAuthorization::class);
        $adminAuthorization->expects(self::once())
            ->method('isAuthorized')
            ->with($this->context, 'MageOS_Blog::author')
            ->willReturn(true);

        self::assertTrue($this->policy(adminAuthorization: $adminAuthorization)->canSeeAuthorEmail($this->context));
        self::assertFalse($this->policy(isAdmin: false)->canSeeAuthorEmail($this->context));
    }

    private function policy(
        bool $isAdmin = false,
        ?AdminAuthorization $adminAuthorization = null,
        bool $blogEnabled = true,
    ): ReadPolicy {
        if ($adminAuthorization === null) {
            $adminAuthorization = $this->createMock(AdminAuthorization::class);
            $adminAuthorization->method('isAuthorized')->willReturn($isAdmin);
        }

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn((string) self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $config = $this->createMock(Config::class);
        $config->method('isEnabled')->with(self::STORE_ID)->willReturn($blogEnabled);

        return new ReadPolicy($adminAuthorization, $storeManager, new StoreVisibility(), $config);
    }
}

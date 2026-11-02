<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\Route as CoreRoute;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;
use MageOS\Blog\Model\Resolver\UrlResolver\UrlEntityReadability;
use MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The route query must answer a blog URL the caller may not read exactly like an unknown URL: null.
 * The data provider loads the entity without seeing the context, so this plugin is the only gate.
 */
final class RouteTest extends TestCase
{
    private PostRepositoryInterface $posts;
    private CategoryRepositoryInterface $categories;
    private TagRepositoryInterface $tags;
    private AuthorRepositoryInterface $authors;
    private ReadPolicy $policy;
    private ContextInterface $context;

    protected function setUp(): void
    {
        $this->posts = $this->createMock(PostRepositoryInterface::class);
        $this->categories = $this->createMock(CategoryRepositoryInterface::class);
        $this->tags = $this->createMock(TagRepositoryInterface::class);
        $this->authors = $this->createMock(AuthorRepositoryInterface::class);
        $this->policy = $this->createMock(ReadPolicy::class);
        $this->context = $this->createMock(ContextInterface::class);
    }

    /**
     * @return array<string, array{string, string, class-string, string}>
     */
    public static function blogTypes(): array
    {
        return [
            'post' => ['BLOG_POST', 'posts', PostInterface::class, 'canReadPost'],
            'category' => ['BLOG_CATEGORY', 'categories', CategoryInterface::class, 'canReadCategory'],
            'tag' => ['BLOG_TAG', 'tags', TagInterface::class, 'canReadTag'],
            'author' => ['BLOG_AUTHOR', 'authors', AuthorInterface::class, 'canReadAuthor'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function blogTypeRepositories(): array
    {
        return array_map(static fn (array $row): array => [$row[0], $row[1]], self::blogTypes());
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function a_readable_blog_entity_is_routed(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $entity = $this->createMock($entityClass);
        $this->{$repository}->method('getById')->with(7)->willReturn($entity);
        $this->policy->method($policyMethod)->with($this->context, $entity)->willReturn(true);
        $result = $this->routeResult($type, 7);

        self::assertSame($result, $this->around(static fn (): array => $result));
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function a_hidden_blog_entity_looks_like_an_unknown_url(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $this->{$repository}->method('getById')->willReturn($this->createMock($entityClass));
        $this->policy->method($policyMethod)->willReturn(false);
        $result = $this->routeResult($type, 7);

        self::assertNull($this->around(static fn (): array => $result));
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function a_hidden_redirect_to_a_blog_entity_looks_like_an_unknown_url(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $this->{$repository}->method('getById')->willReturn($this->createMock($entityClass));
        $this->policy->method($policyMethod)->willReturn(false);
        $result = ['redirect_code' => 301] + $this->routeResult($type, 7);

        self::assertNull($this->around(static fn (): array => $result));
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function a_visible_redirect_keeps_its_code_and_target(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $this->{$repository}->method('getById')->willReturn($this->createMock($entityClass));
        $this->policy->method($policyMethod)->willReturn(true);
        $result = ['redirect_code' => 301, 'relative_url' => 'blog/new-slug'] + $this->routeResult($type, 7);

        self::assertSame($result, $this->around(static fn (): array => $result));
    }

    #[Test]
    #[DataProvider('blogTypeRepositories')]
    public function a_rewrite_whose_entity_is_gone_looks_like_an_unknown_url(string $type, string $repository): void
    {
        $this->{$repository}->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));
        $result = $this->routeResult($type, 7);

        self::assertNull($this->around(static fn (): array => $result));
    }

    #[Test]
    public function any_failure_checking_a_blog_entity_hides_it(): void
    {
        $this->posts->method('getById')->willThrowException(new LocalizedException(__('Database went away.')));
        $result = $this->routeResult('BLOG_POST', 7);

        self::assertNull($this->around(static fn (): array => $result));
    }

    #[Test]
    public function a_blog_entity_with_an_unexpected_context_is_hidden(): void
    {
        $this->posts->expects(self::never())->method('getById');
        $result = $this->routeResult('BLOG_POST', 7);

        self::assertNull($this->around(static fn (): array => $result, new \stdClass()));
    }

    #[Test]
    public function a_blog_result_without_an_id_is_hidden(): void
    {
        $result = ['type' => 'BLOG_POST', 'relative_url' => 'blog/x', 'redirect_code' => 0];

        self::assertNull($this->around(static fn (): array => $result));
    }

    #[Test]
    public function the_provider_not_supporting_the_entity_resolves_to_null(): void
    {
        $proceed = static function (): never {
            throw new RouteNotSupportedException('BLOG_POST');
        };

        self::assertNull($this->around($proceed));
    }

    #[Test]
    public function an_unknown_url_stays_null(): void
    {
        self::assertNull($this->around(static fn (): ?array => null));
    }

    #[Test]
    public function other_modules_results_pass_through_untouched(): void
    {
        $this->posts->expects(self::never())->method('getById');
        $result = ['type' => 'PRODUCT', 'relative_url' => 'a.html', 'redirect_code' => 0, 'id' => 5];

        self::assertSame($result, $this->around(static fn (): array => $result));
    }

    #[Test]
    public function a_result_without_an_entity_type_passes_through_untouched(): void
    {
        $result = ['type' => null, 'relative_url' => 'some/path', 'redirect_code' => 0];

        self::assertSame($result, $this->around(static fn (): array => $result));
    }

    #[Test]
    public function other_errors_are_not_swallowed(): void
    {
        $proceed = static function (): never {
            throw new GraphQlNoSuchEntityException(__('Something else.'));
        };

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->around($proceed);
    }

    #[Test]
    public function the_original_arguments_are_forwarded(): void
    {
        $field = $this->createMock(Field::class);
        $info = $this->createMock(ResolveInfo::class);
        $seen = [];

        $this->plugin()->aroundResolve(
            $this->createMock(CoreRoute::class),
            static function (...$args) use (&$seen): ?array {
                $seen = $args;

                return null;
            },
            $field,
            $this->context,
            $info,
            ['v'],
            ['url' => 'x'],
        );

        self::assertSame([$field, $this->context, $info, ['v'], ['url' => 'x']], $seen);
    }

    /**
     * @return array<string, mixed>
     */
    private function routeResult(string $type, int $id): array
    {
        return ['id' => $id, 'type' => $type, 'relative_url' => 'blog/x', 'redirect_code' => 0];
    }

    private function plugin(): Route
    {
        return new Route(
            new UrlEntityReadability($this->posts, $this->categories, $this->tags, $this->authors, $this->policy)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function around(callable $proceed, mixed $context = null): ?array
    {
        return $this->plugin()->aroundResolve(
            $this->createMock(CoreRoute::class),
            $proceed,
            $this->createMock(Field::class),
            $context ?? $this->context,
            $this->createMock(ResolveInfo::class),
        );
    }
}

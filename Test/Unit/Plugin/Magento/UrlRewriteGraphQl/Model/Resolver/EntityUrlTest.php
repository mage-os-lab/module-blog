<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl as CoreEntityUrl;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver\EntityUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Core's urlResolver answers from url_rewrite rows, which exist for every blog entity
 * whatever its status. The plugin makes a hidden entity look like an unknown URL.
 */
final class EntityUrlTest extends TestCase
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
    public function a_readable_entity_is_returned_untouched(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $entity = $this->createMock($entityClass);
        $this->{$repository}->method('getById')->with(7)->willReturn($entity);
        $this->policy->method($policyMethod)->with($this->context, $entity)->willReturn(true);
        $result = $this->rewriteResult($type, 7);

        self::assertSame($result, $this->resolve($result));
    }

    /**
     * @param class-string $entityClass
     */
    #[Test]
    #[DataProvider('blogTypes')]
    public function a_hidden_entity_looks_like_an_unknown_url(
        string $type,
        string $repository,
        string $entityClass,
        string $policyMethod,
    ): void {
        $entity = $this->createMock($entityClass);
        $this->{$repository}->method('getById')->willReturn($entity);
        $this->policy->method($policyMethod)->willReturn(false);

        self::assertNull($this->resolve($this->rewriteResult($type, 7)));
    }

    #[Test]
    #[DataProvider('blogTypeRepositories')]
    public function a_rewrite_whose_entity_is_gone_looks_like_an_unknown_url(string $type, string $repository): void
    {
        $this->{$repository}->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));

        self::assertNull($this->resolve($this->rewriteResult($type, 7)));
    }

    #[Test]
    public function a_redirect_to_a_hidden_entity_is_hidden_too(): void
    {
        $this->posts->method('getById')->willReturn($this->createMock(PostInterface::class));
        $this->policy->method('canReadPost')->willReturn(false);
        $redirect = $this->rewriteResult('BLOG_POST', 7) + ['redirect_code' => 301, 'redirectCode' => 301];

        self::assertNull($this->resolve($redirect));
    }

    #[Test]
    public function the_entity_type_is_matched_case_insensitively(): void
    {
        $this->posts->expects(self::once())->method('getById')->with(7)
            ->willReturn($this->createMock(PostInterface::class));
        $this->policy->method('canReadPost')->willReturn(false);

        self::assertNull($this->resolve($this->rewriteResult('blog_post', 7)));
    }

    #[Test]
    public function other_modules_entities_are_never_touched(): void
    {
        $this->posts->expects(self::never())->method('getById');
        $this->categories->expects(self::never())->method('getById');
        $result = $this->rewriteResult('PRODUCT', 7);

        self::assertSame($result, $this->resolve($result));
    }

    #[Test]
    public function any_failure_loading_a_blog_entity_hides_it(): void
    {
        $this->posts->method('getById')->willThrowException(new LocalizedException(__('Database went away.')));

        self::assertNull($this->resolve($this->rewriteResult('BLOG_POST', 7)));
    }

    #[Test]
    public function a_blog_entity_with_an_unexpected_context_is_hidden(): void
    {
        $this->posts->expects(self::never())->method('getById');
        $result = $this->rewriteResult('BLOG_POST', 7);

        self::assertNull($this->resolve($result, new \stdClass()));
    }

    #[Test]
    public function other_modules_entities_pass_even_with_an_unexpected_context(): void
    {
        $result = $this->rewriteResult('PRODUCT', 7);

        self::assertSame($result, $this->resolve($result, new \stdClass()));
    }

    #[Test]
    public function an_unknown_url_stays_null(): void
    {
        self::assertNull($this->resolve(null));
    }

    #[Test]
    public function a_result_without_an_entity_is_returned_untouched(): void
    {
        $result = ['id' => null, 'type' => null, 'relative_url' => 'some/path', 'redirect_code' => 0];

        self::assertSame($result, $this->resolve($result));
    }

    /**
     * @return array<string, mixed>
     */
    private function rewriteResult(string $type, int $id): array
    {
        return ['id' => $id, 'type' => $type, 'relative_url' => 'blog/x', 'redirect_code' => 0];
    }

    /**
     * @param array<string, mixed>|null $result
     *
     * @return array<string, mixed>|null
     */
    private function resolve(?array $result, mixed $context = null): ?array
    {
        $plugin = new EntityUrl($this->posts, $this->categories, $this->tags, $this->authors, $this->policy);

        return $plugin->afterResolve(
            $this->createMock(CoreEntityUrl::class),
            $result,
            $this->createMock(Field::class),
            $context ?? $this->context,
            $this->createMock(ResolveInfo::class),
        );
    }
}

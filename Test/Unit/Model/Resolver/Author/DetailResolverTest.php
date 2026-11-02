<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\Resolver\Author\DetailResolver;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DetailResolverTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function it_returns_an_author_the_caller_may_read(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getBySlug')->with('jane')->willReturn($this->createMock(AuthorInterface::class));

        $result = $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['slug' => 'jane']);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_author_fails_exactly_like_a_missing_one_by_id(): void
    {
        $hidden = $this->createMock(AuthorRepositoryInterface::class);
        $hidden->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $missing = $this->createMock(AuthorRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('authorId', 7));

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), ['id' => 7]),
            $this->messageOf($this->resolver($hidden, readable: false), ['id' => 7]),
        );
    }

    #[Test]
    public function a_hidden_author_fails_exactly_like_a_missing_one_by_slug(): void
    {
        $hidden = $this->createMock(AuthorRepositoryInterface::class);
        $hidden->method('getBySlug')->willReturn($this->createMock(AuthorInterface::class));
        $missing = $this->createMock(AuthorRepositoryInterface::class);
        $missing->method('getBySlug')->willThrowException(NoSuchEntityException::singleField('slug', 'jane'));

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), ['slug' => 'jane']),
            $this->messageOf($this->resolver($hidden, readable: false), ['slug' => 'jane']),
        );
    }

    /**
     * @param array<string, mixed> $args
     */
    private function messageOf(DetailResolver $resolver, array $args): string
    {
        try {
            $resolver->resolve($this->field(), $this->context(), $this->info(), null, $args);
        } catch (GraphQlNoSuchEntityException $e) {
            return $e->getMessage();
        }

        self::fail('Expected a not-found error.');
    }

    private function resolver(AuthorRepositoryInterface $repository, bool $readable): DetailResolver
    {
        $mapper = $this->createMock(AuthorMapper::class);
        $mapper->method('toArray')->willReturn(['id' => 7]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn($readable);

        return new DetailResolver($repository, $mapper, $policy);
    }
}

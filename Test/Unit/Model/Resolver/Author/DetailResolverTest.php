<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Model\Resolver\Author\DetailResolver;
use MageOS\Blog\Model\Resolver\Mapper\AuthorMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\UidCodec;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use MageOS\Blog\Test\Unit\Model\Resolver\UidCodecTest;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[Test]
    public function it_returns_a_author_the_caller_may_read_by_uid(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($this->createMock(AuthorInterface::class));

        $result = $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_author_fails_exactly_like_a_missing_one_by_uid(): void
    {
        $hidden = $this->createMock(AuthorRepositoryInterface::class);
        $hidden->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $missing = $this->createMock(AuthorRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));

        $args = ['uid' => $this->uid(7)];

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), $args),
            $this->messageOf($this->resolver($hidden, readable: false), $args),
        );
    }

    #[Test]
    public function a_hidden_author_is_never_mapped_by_uid(): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(AuthorInterface::class));
        $resolver = $this->resolver($repository, readable: false, neverMap: true);

        $this->expectException(GraphQlNoSuchEntityException::class);

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);
    }

    #[Test]
    #[DataProvider('invalid_uids')]
    public function an_invalid_uid_fails_exactly_like_an_unknown_one_and_never_hits_the_repository(string $uid): void
    {
        $missing = $this->createMock(AuthorRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 99));
        $untouched = $this->createMock(AuthorRepositoryInterface::class);
        $untouched->expects(self::never())->method('getById');

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), ['uid' => $this->uid(99)]),
            $this->messageOf($this->resolver($untouched, readable: true), ['uid' => $uid]),
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalid_uids(): array
    {
        // An empty uid counts as "not given" (see the combination tests), so it is not in this list.
        $uids = UidCodecTest::invalid_uids();
        unset($uids['empty']);

        return $uids + ['blank' => [' ']];
    }

    #[Test]
    #[DataProvider('bad_argument_combinations')]
    public function it_demands_exactly_one_of_id_uid_and_slug(array $args): void
    {
        $repository = $this->createMock(AuthorRepositoryInterface::class);
        $repository->expects(self::never())->method('getById');

        $this->expectException(GraphQlInputException::class);

        $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, $args);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function bad_argument_combinations(): array
    {
        return [
            'none' => [[]],
            'empty uid alone' => [['uid' => '']],
            'id and uid' => [['id' => 7, 'uid' => 'Nw==']],
            'uid and slug' => [['uid' => 'Nw==', 'slug' => 'x']],
            'id and slug' => [['id' => 7, 'slug' => 'x']],
            'all three' => [['id' => 7, 'uid' => 'Nw==', 'slug' => 'x']],
        ];
    }

    private function uid(int $id): string
    {
        return (new UidCodec(new Uid()))->encode($id);
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

    private function resolver(AuthorRepositoryInterface $repository, bool $readable, bool $neverMap = false): DetailResolver
    {
        $mapper = $this->createMock(AuthorMapper::class);
        if ($neverMap) {
            $mapper->expects(self::never())->method('toArray');
        } else {
            $mapper->method('toArray')->willReturn(['id' => 7]);
        }
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadAuthor')->willReturn($readable);

        return new DetailResolver($repository, $mapper, $policy, new UidCodec(new Uid()));
    }
}

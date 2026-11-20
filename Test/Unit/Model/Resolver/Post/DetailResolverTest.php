<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\PostMapper;
use MageOS\Blog\Model\Resolver\Post\DetailResolver;
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

    private const STORE_ID = 3;

    #[Test]
    public function it_returns_a_post_the_caller_may_read_by_id(): void
    {
        $post = $this->createMock(PostInterface::class);
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($post);

        $result = $this->resolver($repository, readable: true)->resolve(
            $this->field(),
            $this->context(),
            $this->info(),
            null,
            ['id' => 7]
        );

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function it_returns_a_post_the_caller_may_read_by_url_key_in_the_current_store(): void
    {
        $post = $this->createMock(PostInterface::class);
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getByUrlKey')->with('hello', self::STORE_ID)->willReturn($post);

        $result = $this->resolver($repository, readable: true)->resolve(
            $this->field(),
            $this->context(),
            $this->info(),
            null,
            ['url_key' => 'hello']
        );

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_post_fails_exactly_like_a_missing_one_by_id(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(PostInterface::class));
        $missing = $this->createMock(PostRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('postId', 7));

        $hiddenMessage = $this->messageOf($this->resolver($repository, readable: false), ['id' => 7]);
        $missingMessage = $this->messageOf($this->resolver($missing, readable: true), ['id' => 7]);

        self::assertSame($missingMessage, $hiddenMessage);
    }

    #[Test]
    public function a_hidden_post_fails_exactly_like_a_missing_one_by_url_key(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getByUrlKey')->willReturn($this->createMock(PostInterface::class));
        $missing = $this->createMock(PostRepositoryInterface::class);
        $missing->method('getByUrlKey')->willThrowException(
            NoSuchEntityException::doubleField('urlKey', 'secret-draft', 'storeId', self::STORE_ID)
        );

        $hiddenMessage = $this->messageOf($this->resolver($repository, readable: false), ['url_key' => 'secret-draft']);
        $missingMessage = $this->messageOf($this->resolver($missing, readable: true), ['url_key' => 'secret-draft']);

        self::assertSame($missingMessage, $hiddenMessage);
    }

    #[Test]
    public function a_hidden_post_is_never_mapped(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(PostInterface::class));
        $mapper = $this->createMock(PostMapper::class);
        $mapper->expects(self::never())->method('toArray');

        $this->expectException(GraphQlNoSuchEntityException::class);

        $this->resolver($repository, readable: false, mapper: $mapper)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['id' => 7]);
    }

    #[Test]
    public function it_still_demands_exactly_one_of_id_and_url_key(): void
    {
        $this->expectException(GraphQlInputException::class);

        $this->resolver($this->createMock(PostRepositoryInterface::class), readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['id' => 7, 'url_key' => 'hello']);
    }

    #[Test]
    public function it_returns_a_post_the_caller_may_read_by_uid(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($this->createMock(PostInterface::class));

        $result = $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_post_fails_exactly_like_a_missing_one_by_uid(): void
    {
        $hidden = $this->createMock(PostRepositoryInterface::class);
        $hidden->method('getById')->willReturn($this->createMock(PostInterface::class));
        $missing = $this->createMock(PostRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));

        $args = ['uid' => $this->uid(7)];

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), $args),
            $this->messageOf($this->resolver($hidden, readable: false), $args),
        );
    }

    #[Test]
    public function a_hidden_post_is_never_mapped_by_uid(): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(PostInterface::class));
        $mapper = $this->createMock(PostMapper::class);
        $mapper->expects(self::never())->method('toArray');
        $resolver = $this->resolver($repository, readable: false, mapper: $mapper);

        $this->expectException(GraphQlNoSuchEntityException::class);

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);
    }

    #[Test]
    #[DataProvider('invalid_uids')]
    public function an_invalid_uid_fails_exactly_like_an_unknown_one_and_never_hits_the_repository(string $uid): void
    {
        $missing = $this->createMock(PostRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 99));
        $untouched = $this->createMock(PostRepositoryInterface::class);
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
    public function it_demands_exactly_one_of_id_uid_and_url_key(array $args): void
    {
        $repository = $this->createMock(PostRepositoryInterface::class);
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
            'uid and url_key' => [['uid' => 'Nw==', 'url_key' => 'x']],
            'id and url_key' => [['id' => 7, 'url_key' => 'x']],
            'all three' => [['id' => 7, 'uid' => 'Nw==', 'url_key' => 'x']],
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

    private function resolver(
        PostRepositoryInterface $repository,
        bool $readable,
        ?PostMapper $mapper = null,
    ): DetailResolver {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn((string) self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        if ($mapper === null) {
            $mapper = $this->createMock(PostMapper::class);
            $mapper->method('toArray')->willReturn(['id' => 7]);
        }

        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadPost')->willReturn($readable);

        return new DetailResolver($repository, $storeManager, $mapper, $policy, new UidCodec(new Uid()));
    }
}

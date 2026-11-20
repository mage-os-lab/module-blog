<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Tag;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\Tag\DetailResolver;
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
    public function it_returns_a_tag_the_caller_may_read(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($this->createMock(TagInterface::class));

        $result = $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['id' => 7]);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_tag_fails_exactly_like_a_missing_one_by_id(): void
    {
        $hidden = $this->createMock(TagRepositoryInterface::class);
        $hidden->method('getById')->willReturn($this->createMock(TagInterface::class));
        $missing = $this->createMock(TagRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('tagId', 7));

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), ['id' => 7]),
            $this->messageOf($this->resolver($hidden, readable: false), ['id' => 7]),
        );
    }

    #[Test]
    public function a_hidden_tag_fails_exactly_like_a_missing_one_by_url_key(): void
    {
        $hidden = $this->createMock(TagRepositoryInterface::class);
        $hidden->method('getByUrlKey')->willReturn($this->createMock(TagInterface::class));
        $missing = $this->createMock(TagRepositoryInterface::class);
        $missing->method('getByUrlKey')->willThrowException(
            NoSuchEntityException::doubleField('urlKey', 'old', 'storeId', self::STORE_ID)
        );

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), ['url_key' => 'old']),
            $this->messageOf($this->resolver($hidden, readable: false), ['url_key' => 'old']),
        );
    }

    #[Test]
    public function it_returns_a_tag_the_caller_may_read_by_uid(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->with(7)->willReturn($this->createMock(TagInterface::class));

        $result = $this->resolver($repository, readable: true)
            ->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);

        self::assertSame(['id' => 7], $result);
    }

    #[Test]
    public function a_hidden_tag_fails_exactly_like_a_missing_one_by_uid(): void
    {
        $hidden = $this->createMock(TagRepositoryInterface::class);
        $hidden->method('getById')->willReturn($this->createMock(TagInterface::class));
        $missing = $this->createMock(TagRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 7));

        $args = ['uid' => $this->uid(7)];

        self::assertSame(
            $this->messageOf($this->resolver($missing, readable: true), $args),
            $this->messageOf($this->resolver($hidden, readable: false), $args),
        );
    }

    #[Test]
    public function a_hidden_tag_is_never_mapped_by_uid(): void
    {
        $repository = $this->createMock(TagRepositoryInterface::class);
        $repository->method('getById')->willReturn($this->createMock(TagInterface::class));
        $resolver = $this->resolver($repository, readable: false, neverMap: true);

        $this->expectException(GraphQlNoSuchEntityException::class);

        $resolver->resolve($this->field(), $this->context(), $this->info(), null, ['uid' => $this->uid(7)]);
    }

    #[Test]
    #[DataProvider('invalid_uids')]
    public function an_invalid_uid_fails_exactly_like_an_unknown_one_and_never_hits_the_repository(string $uid): void
    {
        $missing = $this->createMock(TagRepositoryInterface::class);
        $missing->method('getById')->willThrowException(NoSuchEntityException::singleField('id', 99));
        $untouched = $this->createMock(TagRepositoryInterface::class);
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
        $repository = $this->createMock(TagRepositoryInterface::class);
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

    private function resolver(TagRepositoryInterface $repository, bool $readable, bool $neverMap = false): DetailResolver
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn((string) self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $mapper = $this->createMock(TagMapper::class);
        if ($neverMap) {
            $mapper->expects(self::never())->method('toArray');
        } else {
            $mapper->method('toArray')->willReturn(['id' => 7]);
        }
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadTag')->willReturn($readable);

        return new DetailResolver($repository, $storeManager, $mapper, $policy, new UidCodec(new Uid()));
    }
}

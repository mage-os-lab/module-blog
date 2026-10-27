<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Tag;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Resolver\Mapper\TagMapper;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Model\Resolver\Tag\DetailResolver;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
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

    private function resolver(TagRepositoryInterface $repository, bool $readable): DetailResolver
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn((string) self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $mapper = $this->createMock(TagMapper::class);
        $mapper->method('toArray')->willReturn(['id' => 7]);
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canReadTag')->willReturn($readable);

        return new DetailResolver($repository, $storeManager, $mapper, $policy);
    }
}

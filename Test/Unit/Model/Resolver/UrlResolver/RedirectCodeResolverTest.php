<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\UrlResolver;

use MageOS\Blog\Model\Resolver\UrlResolver\RedirectCodeResolver;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RedirectCodeResolverTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function it_returns_the_code_the_route_query_found(): void
    {
        self::assertSame(301, $this->resolve(['id' => 1, 'redirect_code' => 301]));
    }

    #[Test]
    public function it_is_zero_outside_the_route_query(): void
    {
        self::assertSame(0, $this->resolve(['id' => 1]));
    }

    /**
     * @param array<string, mixed> $value
     */
    private function resolve(array $value): int
    {
        return (new RedirectCodeResolver())->resolve($this->field(), $this->context(), $this->info(), $value);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\UrlResolver;

use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;
use MageOS\Blog\Model\Resolver\UrlResolver\UnsupportedRouteProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UnsupportedRouteProviderTest extends TestCase
{
    #[Test]
    public function it_refuses_every_blog_entity_type(): void
    {
        $this->expectException(RouteNotSupportedException::class);

        (new UnsupportedRouteProvider())->getData('BLOG_POST', 7);
    }
}

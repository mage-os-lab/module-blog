<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\UrlRewriteGraphQl\Model\Resolver\Route as CoreRoute;
use MageOS\Blog\Model\Resolver\UrlResolver\RouteNotSupportedException;
use MageOS\Blog\Plugin\Magento\UrlRewriteGraphQl\Model\Resolver\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Core's route query dereferences a data provider per entity type. Blog types have none, so a blog
 * URL crashed while an unknown URL returned null: a visitor could tell the two apart.
 */
final class RouteTest extends TestCase
{
    #[Test]
    public function a_blog_url_resolves_to_null_like_an_unknown_one(): void
    {
        $proceed = static function (): never {
            throw new RouteNotSupportedException('BLOG_POST');
        };

        self::assertNull($this->around($proceed));
    }

    #[Test]
    public function other_results_pass_through_untouched(): void
    {
        $result = ['type' => 'PRODUCT', 'relative_url' => 'a.html', 'redirect_code' => 0];

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
        $context = $this->createMock(ContextInterface::class);
        $info = $this->createMock(ResolveInfo::class);
        $seen = [];

        (new Route())->aroundResolve(
            $this->createMock(CoreRoute::class),
            static function (...$args) use (&$seen): ?array {
                $seen = $args;

                return null;
            },
            $field,
            $context,
            $info,
            ['v'],
            ['url' => 'x'],
        );

        self::assertSame([$field, $context, $info, ['v'], ['url' => 'x']], $seen);
    }

    private function around(callable $proceed): ?array
    {
        return (new Route())->aroundResolve(
            $this->createMock(CoreRoute::class),
            $proceed,
            $this->createMock(Field::class),
            $this->createMock(ContextInterface::class),
            $this->createMock(ResolveInfo::class),
        );
    }
}

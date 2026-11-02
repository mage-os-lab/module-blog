<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Author;

use MageOS\Blog\Model\Resolver\Author\EmailResolver;
use MageOS\Blog\Model\Resolver\ReadPolicy;
use MageOS\Blog\Test\Unit\Model\Resolver\ResolverTestTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EmailResolverTest extends TestCase
{
    use ResolverTestTrait;

    #[Test]
    public function an_admin_with_the_author_resource_reads_the_email(): void
    {
        self::assertSame('jane@example.com', $this->resolve(true, ['email' => 'jane@example.com']));
    }

    #[Test]
    public function everyone_else_gets_null(): void
    {
        self::assertNull($this->resolve(false, ['email' => 'jane@example.com']));
    }

    #[Test]
    public function an_author_without_an_email_is_null_for_an_admin_too(): void
    {
        self::assertNull($this->resolve(true, ['email' => null]));
        self::assertNull($this->resolve(true, []));
    }

    /**
     * @param array<string, mixed> $author
     */
    private function resolve(bool $canSee, array $author): ?string
    {
        $context = $this->context();
        $policy = $this->createMock(ReadPolicy::class);
        $policy->method('canSeeAuthorEmail')->with($context)->willReturn($canSee);

        return (new EmailResolver($policy))->resolve($this->field(), $context, $this->info(), $author);
    }
}

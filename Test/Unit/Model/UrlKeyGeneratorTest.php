<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\UrlKeyGenerator;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UrlKeyGeneratorTest extends TestCase
{
    private CollisionChecker $checker;
    private UrlKeyGenerator $generator;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(CollisionChecker::class);
        $this->checker->method('isTaken')->willReturn(false);
        $this->generator = new UrlKeyGenerator($this->checker);
    }

    #[Test]
    #[DataProvider('normalizationCases')]
    public function normalizes_title_to_slug(string $title, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->generator->generate($title, UrlKeyGeneratorInterface::ENTITY_POST)
        );
    }

    public static function normalizationCases(): array
    {
        return [
            'simple'              => ['Hello World', 'hello-world'],
            'trailing punctuation' => ['Hello, World!', 'hello-world'],
            'unicode accents'     => ['Café naïve', 'cafe-naive'],
            'multiple spaces'     => ['a   b   c', 'a-b-c'],
            'leading slashes'     => ['/hello/world/', 'hello-world'],
            'emoji'               => ['Ship 🚀 it', 'ship-it'],
            'numbers'             => ['Top 10 Tips', 'top-10-tips'],
        ];
    }

    #[Test]
    public function rejects_reserved_slug(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->generator->validate('category', UrlKeyGeneratorInterface::ENTITY_POST, 1);
    }

    #[Test]
    public function appends_suffix_on_collision(): void
    {
        $checker = $this->createMock(CollisionChecker::class);
        $checker->expects(self::exactly(3))
            ->method('isTaken')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $generator = new UrlKeyGenerator($checker);

        self::assertSame('hello-3', $generator->generate('hello', UrlKeyGeneratorInterface::ENTITY_POST));
    }
}

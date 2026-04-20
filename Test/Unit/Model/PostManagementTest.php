<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use Magento\Framework\App\ResourceConnection;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\PostManagement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PostManagementTest extends TestCase
{
    private PostManagement $management;

    protected function setUp(): void
    {
        $this->management = new PostManagement(
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(ResourceConnection::class),
        );
    }

    #[Test]
    #[DataProvider('readingTimeCases')]
    public function compute_reading_time_returns_minutes(string $content, int $expectedMinutes): void
    {
        self::assertSame($expectedMinutes, $this->management->computeReadingTime($content));
    }

    public static function readingTimeCases(): array
    {
        return [
            'empty string returns zero' => ['', 0],
            'whitespace only returns zero' => ["   \n\t   ", 0],
            'short content floors to 1 minute' => ['One sentence of five words.', 1],
            'strips HTML tags' => [
                '<p>one two three</p><div>four five six</div>',
                1,
            ],
            '200 words is exactly 1 minute' => [str_repeat('word ', 200), 1],
            '201 words rounds up to 2 minutes' => [str_repeat('word ', 201), 2],
            '600 words rounds up to 3 minutes' => [str_repeat('word ', 600), 3],
            'HTML-only content returns 0' => ['<img src="x.jpg" /><br/>', 0],
        ];
    }
}

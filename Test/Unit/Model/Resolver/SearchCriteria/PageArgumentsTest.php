<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\SearchCriteria;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Model\Resolver\SearchCriteria\PageArguments;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PageArgumentsTest extends TestCase
{
    #[Test]
    public function it_defaults_to_the_first_page_of_twenty(): void
    {
        self::assertSame(['pageSize' => 20, 'currentPage' => 1], (new PageArguments())->normalize([]));
    }

    #[Test]
    public function it_passes_valid_values_through(): void
    {
        self::assertSame(
            ['pageSize' => 50, 'currentPage' => 3],
            (new PageArguments())->normalize(['pageSize' => 50, 'currentPage' => 3])
        );
    }

    #[Test]
    public function it_accepts_the_maximum(): void
    {
        self::assertSame(
            PageArguments::MAX_PAGE_SIZE,
            (new PageArguments())->normalize(['pageSize' => PageArguments::MAX_PAGE_SIZE])['pageSize']
        );
    }

    #[Test]
    public function the_maximum_is_one_hundred(): void
    {
        self::assertSame(100, PageArguments::MAX_PAGE_SIZE);
    }

    #[Test]
    #[DataProvider('oversizedPageSizes')]
    public function it_clamps_a_page_size_above_the_maximum(int $requested): void
    {
        self::assertSame(100, (new PageArguments())->normalize(['pageSize' => $requested])['pageSize']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function oversizedPageSizes(): array
    {
        return ['one over' => [101], 'huge' => [1000000], 'int max' => [PHP_INT_MAX]];
    }

    #[Test]
    #[DataProvider('invalidPageSizes')]
    public function it_rejects_a_page_size_below_one(int $requested): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('pageSize value must be greater than 0.');

        (new PageArguments())->normalize(['pageSize' => $requested]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidPageSizes(): array
    {
        return ['zero, which would drop the SQL limit' => [0], 'negative' => [-1]];
    }

    #[Test]
    #[DataProvider('invalidCurrentPages')]
    public function it_rejects_a_current_page_below_one(int $requested): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('currentPage value must be greater than 0.');

        (new PageArguments())->normalize(['currentPage' => $requested]);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidCurrentPages(): array
    {
        return ['zero' => [0], 'negative' => [-3]];
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Post;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\ViewModel\Post\Listing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ListingTest extends TestCase
{
    #[Test]
    public function returns_valid_archive_month(): void
    {
        $listing = $this->createListing('2026-06');

        self::assertSame('2026-06', $listing->getArchiveMonth());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidArchiveMonths(): array
    {
        return [
            'blank' => [''],
            'missing leading zero' => ['2026-6'],
            'invalid month' => ['2026-13'],
            'extra characters' => ['2026-06-01'],
            'not a date' => ['latest'],
        ];
    }

    #[Test]
    #[DataProvider('invalidArchiveMonths')]
    public function ignores_invalid_archive_month(string $month): void
    {
        $listing = $this->createListing($month);

        self::assertNull($listing->getArchiveMonth());
    }

    private function createListing(string $archiveMonth): Listing
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')
            ->with('archive', '')
            ->willReturn($archiveMonth);

        return new Listing(
            $this->createMock(PostRepositoryInterface::class),
            $this->createMock(SearchCriteriaBuilder::class),
            $this->createMock(SortOrderBuilder::class),
            $this->createMock(StoreManagerInterface::class),
            $request,
            $this->createMock(UrlInterface::class),
            $this->createMock(Config::class),
            $this->createMock(AuthorRepositoryInterface::class)
        );
    }
}

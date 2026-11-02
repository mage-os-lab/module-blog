<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\SearchCriteria;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterKeyNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FilterKeyNormalizerTest extends TestCase
{
    private const ALIASES = ['category' => 'category_id', 'store' => 'store_id'];

    #[Test]
    public function an_unsuffixed_key_is_renamed_to_the_legacy_key(): void
    {
        self::assertEquals(
            ['category_id' => ['eq' => '3'], 'title' => ['match' => 'x']],
            (new FilterKeyNormalizer())->normalize(
                ['category' => ['eq' => '3'], 'title' => ['match' => 'x']],
                self::ALIASES
            )
        );
    }

    #[Test]
    public function a_legacy_key_passes_through(): void
    {
        self::assertSame(
            ['store_id' => ['eq' => '1']],
            (new FilterKeyNormalizer())->normalize(['store_id' => ['eq' => '1']], self::ALIASES)
        );
    }

    #[Test]
    public function both_spellings_of_one_filter_are_rejected(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('"category" and "category_id"');

        (new FilterKeyNormalizer())->normalize(
            ['category' => ['eq' => '3'], 'category_id' => ['eq' => '4']],
            self::ALIASES
        );
    }

    #[Test]
    public function a_null_spelling_does_not_count_as_sent(): void
    {
        self::assertSame(
            ['category_id' => ['eq' => '4']],
            (new FilterKeyNormalizer())->normalize(['category' => null, 'category_id' => ['eq' => '4']], self::ALIASES)
        );
    }
}

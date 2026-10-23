<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use MageOS\Blog\Model\StoreVisibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StoreVisibilityTest extends TestCase
{
    /**
     * @dataProvider cases
     *
     * @param int[] $entityStoreIds
     */
    #[Test]
    #[DataProvider('cases')]
    public function is_visible(array $entityStoreIds, int $storeId, bool $expected): void
    {
        self::assertSame($expected, (new StoreVisibility())->isVisible($entityStoreIds, $storeId));
    }

    /**
     * @return array<string, array{int[], int, bool}>
     */
    public static function cases(): array
    {
        return [
            'assigned to the store' => [[2], 2, true],
            'assigned to several incl. the store' => [[1, 2, 3], 2, true],
            'assigned to all store views' => [[0], 5, true],
            'all store views alongside a specific one' => [[0, 1], 5, true],
            'assigned to no store' => [[], 3, true],
            'assigned to a different store only' => [[1], 2, false],
            'assigned to several, none of them the store' => [[1, 3], 2, false],
        ];
    }
}

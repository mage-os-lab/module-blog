<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\ViewModel\Search;

use ErrorException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\ViewModel\Search\Results;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ResultsTest extends TestCase
{
    #[Test]
    #[DataProvider('queryCases')]
    public function get_query_only_accepts_a_string_param(mixed $param, string $expected): void
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn($param);
        $results = new Results(
            $request,
            $this->createStub(ResourceConnection::class),
            $this->createStub(PostRepositoryInterface::class),
            $this->createStub(StoreManagerInterface::class),
            $this->createStub(Config::class),
            $this->createStub(UrlInterface::class),
            $this->createStub(AuthorRepositoryInterface::class)
        );
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        }, E_WARNING | E_NOTICE);

        try {
            self::assertSame($expected, $results->getQuery());
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function queryCases(): array
    {
        return [
            'plain string'  => ['  magento ', 'magento'],
            'empty string'  => ['', ''],
            'array (q[]=x)' => [['x'], ''],
            'nested array'  => [['a' => ['b']], ''],
        ];
    }
}

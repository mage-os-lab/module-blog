<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Query\Uid;
use MageOS\Blog\Model\Resolver\UidCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class UidCodecTest extends TestCase
{
    #[Test]
    public function it_encodes_exactly_like_the_core_uid_class(): void
    {
        self::assertSame((new Uid())->encode('42'), $this->codec()->encode(42));
        self::assertSame('NDI=', $this->codec()->encode(42));
    }

    #[Test]
    #[DataProvider('valid_ids')]
    public function it_round_trips_valid_ids(int $id): void
    {
        self::assertSame($id, $this->codec()->decode($this->codec()->encode($id)));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function valid_ids(): array
    {
        return [
            'one' => [1],
            'two digits' => [42],
            'large' => [123456789],
            'int32 max' => [2147483647],
        ];
    }

    #[Test]
    #[DataProvider('invalid_uids')]
    public function it_returns_null_for_anything_that_is_not_a_canonical_uid_of_a_positive_int(string $uid): void
    {
        self::assertNull($this->codec()->decode($uid));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalid_uids(): array
    {
        return [
            'empty' => [''],
            'not base64' => ['!!!'],
            'plain number' => ['42'],
            'malformed base64 chars' => ['NDI$'],
            'missing padding' => ['NDI'],
            'extra padding' => ['NDI=='],
            'padding in the middle' => ['ND=I'],
            'url safe alphabet' => ['Pz8_'],
            'leading space' => [' NDI='],
            'trailing space' => ['NDI= '],
            'trailing newline' => ["NDI=\n"],
            'inner whitespace' => ['ND I='],
            'non numeric' => [base64_encode('abc')],
            'zero' => [base64_encode('0')],
            'negative' => [base64_encode('-5')],
            'decimal' => [base64_encode('4.2')],
            'exponent' => [base64_encode('1e3')],
            'leading zero' => [base64_encode('042')],
            'leading plus' => [base64_encode('+42')],
            'padded with spaces' => [base64_encode(' 42')],
            'hex' => [base64_encode('0x2A')],
            'over int32' => [base64_encode('2147483648')],
            'huge number' => [base64_encode('99999999999999999999999999')],
            'huge input' => [str_repeat('QQ==', 100000)],
            'huge digits' => [base64_encode(str_repeat('9', 100000))],
            'nul byte' => [base64_encode("4\0")],
            'binary' => [base64_encode("\xff\xfe")],
        ];
    }

    private function codec(): UidCodec
    {
        return new UidCodec(new Uid());
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\Permalink;
use MageOS\Blog\Model\UrlKeyGenerator;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use MageOS\Blog\Model\UrlKeyGenerator\SlugNormalizer;
use MageOS\Blog\Test\Unit\Model\Url\PermalinkFixture;
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
        $this->generator = $this->generator($this->checker);
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
        $generator = $this->generator($checker);

        self::assertSame('hello-3', $generator->generate('hello', UrlKeyGeneratorInterface::ENTITY_POST));
    }
    #[Test]
    public function rejects_a_permalink_segment_configured_on_any_store_view(): void
    {
        $permalink = PermalinkFixture::create([], [2 => [Config::XML_PATH_PERMALINK_CATEGORY => 'categorie']]);
        $generator = $this->generator($this->checker, $permalink);

        $this->expectException(\InvalidArgumentException::class);
        $generator->validate('categorie', UrlKeyGeneratorInterface::ENTITY_POST, 1);
    }

    #[Test]
    public function suffixes_a_title_that_normalizes_to_a_configured_segment(): void
    {
        $permalink = PermalinkFixture::create([Config::XML_PATH_PERMALINK_AUTHOR => 'auteur']);

        $this->expectException(\InvalidArgumentException::class);
        $this->generator($this->checker, $permalink)->generate('Auteur', UrlKeyGeneratorInterface::ENTITY_POST);
    }

    private function generator(CollisionChecker $checker, ?Permalink $permalink = null): UrlKeyGenerator
    {
        $stores = [];
        foreach ([1, 2] as $id) {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);
            $stores[] = $store;
        }
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        return new UrlKeyGenerator($checker, new SlugNormalizer(), $permalink ?? PermalinkFixture::create(), $storeManager);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Sitemap\ItemProvider;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Sitemap\ItemProvider\Category;
use MageOS\Blog\Model\Sitemap\ItemProvider\Post;
use MageOS\Blog\Model\Sitemap\ItemProvider\Tag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SitemapItemFactoryStub.php';

final class ItemProvidersEnabledFlagTest extends TestCase
{
    #[Test]
    #[DataProvider('providerCases')]
    public function a_disabled_blog_contributes_no_sitemap_items(string $providerClass, string $repositoryClass): void
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(false);
        $config->method('isSitemapEntityEnabled')->willReturn(true);
        $repository = $this->createMock($repositoryClass);
        $repository->expects(self::never())->method('getList');

        $provider = $this->provider($providerClass, $repository, $config);

        self::assertSame([], $provider->getItems(1));
    }

    /**
     * @return array<string, array{0: class-string, 1: class-string}>
     */
    public static function providerCases(): array
    {
        return [
            'posts'      => [Post::class, PostRepositoryInterface::class],
            'categories' => [Category::class, CategoryRepositoryInterface::class],
            'tags'       => [Tag::class, TagRepositoryInterface::class],
        ];
    }

    /**
     * @param class-string $providerClass
     */
    private function provider(string $providerClass, object $repository, Config $config): ItemProviderInterface
    {
        $provider = new $providerClass(
            $repository,
            $this->createStub(SearchCriteriaBuilder::class),
            $this->createStub(SitemapItemInterfaceFactory::class),
            $config
        );
        self::assertInstanceOf(ItemProviderInterface::class, $provider);

        return $provider;
    }
}

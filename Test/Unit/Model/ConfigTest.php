<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use MageOS\Blog\Model\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    #[Test]
    public function is_enabled_reads_flag_via_scope_config(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('isSetFlag')
            ->with('mageos_blog/general/enabled', ScopeInterface::SCOPE_STORE, 5)
            ->willReturn(true);

        $config = new Config($scopeConfig);

        self::assertTrue($config->isEnabled(5));
    }

    #[Test]
    public function get_posts_per_page_coerces_string_to_int(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('mageos_blog/post/posts_per_page', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('10');

        $config = new Config($scopeConfig);

        self::assertSame(10, $config->getPostsPerPage());
    }

    #[Test]
    public function get_default_robots_returns_empty_string_when_null(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(null);

        $config = new Config($scopeConfig);

        self::assertSame('', $config->getDefaultRobots());
    }

    #[Test]
    public function sidebar_widget_enabled_constructs_dynamic_path(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('isSetFlag')
            ->with('mageos_blog/sidebar/recent_posts_enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(true);

        $config = new Config($scopeConfig);

        self::assertTrue($config->isSidebarWidgetEnabled('recent_posts'));
    }

    #[Test]
    public function sidebar_widget_sort_order_constructs_dynamic_path(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('mageos_blog/sidebar/tag_cloud_sort_order', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('40');

        $config = new Config($scopeConfig);

        self::assertSame(40, $config->getSidebarWidgetSortOrder('tag_cloud'));
    }

    #[Test]
    public function sitemap_entity_enabled_constructs_dynamic_path(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('isSetFlag')
            ->with('mageos_blog/sitemap/category/enabled', ScopeInterface::SCOPE_STORE, null)
            ->willReturn(false);

        $config = new Config($scopeConfig);

        self::assertFalse($config->isSitemapEntityEnabled('category'));
    }

    #[Test]
    public function social_networks_splits_comma_list(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('facebook,x,linkedin');

        $config = new Config($scopeConfig);

        self::assertSame(['facebook', 'x', 'linkedin'], $config->getSocialNetworks());
    }

    #[Test]
    public function social_networks_returns_empty_array_when_blank(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn('');

        $config = new Config($scopeConfig);

        self::assertSame([], $config->getSocialNetworks());
    }

    #[Test]
    public function social_networks_trims_and_filters_empty_entries(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(' facebook , , linkedin ');

        $config = new Config($scopeConfig);

        self::assertSame(['facebook', 'linkedin'], $config->getSocialNetworks());
    }

    #[Test]
    public function rss_limit_coerces_to_int(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects(self::once())
            ->method('getValue')
            ->with('mageos_blog/rss/limit', ScopeInterface::SCOPE_STORE, null)
            ->willReturn('20');

        $config = new Config($scopeConfig);

        self::assertSame(20, $config->getRssLimit());
    }
}

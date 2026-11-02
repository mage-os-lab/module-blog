<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Block;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Theme\Block\Html\Breadcrumbs as ThemeBreadcrumbs;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Block\Breadcrumbs;
use MageOS\Blog\Controller\Post\View as PostViewController;
use MageOS\Blog\Model\Breadcrumb\PostCategoryPicker;
use MageOS\Blog\Model\Breadcrumb\TrailBuilder;
use MageOS\Blog\Model\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BreadcrumbsTest extends TestCase
{
    #[Test]
    public function registers_the_index_trail_on_the_blog_index(): void
    {
        $theme = $this->createMock(ThemeBreadcrumbs::class);
        $theme->expects(self::exactly(2))->method('addCrumb');

        $this->prepare($theme, 'blog_index_index', true, true);
    }

    #[Test]
    public function registers_nothing_when_the_trail_is_switched_off(): void
    {
        $theme = $this->createMock(ThemeBreadcrumbs::class);
        $theme->expects(self::never())->method('addCrumb');

        $this->prepare($theme, 'blog_index_index', true, false);
    }

    #[Test]
    public function registers_nothing_when_the_module_is_disabled(): void
    {
        $theme = $this->createMock(ThemeBreadcrumbs::class);
        $theme->expects(self::never())->method('addCrumb');

        $this->prepare($theme, 'blog_index_index', false, true);
    }

    #[Test]
    public function does_not_fatal_when_the_theme_has_no_breadcrumbs_block(): void
    {
        $this->prepare(false, 'blog_index_index', true, true);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function registers_nothing_on_an_unknown_action(): void
    {
        $theme = $this->createMock(ThemeBreadcrumbs::class);
        $theme->expects(self::never())->method('addCrumb');

        $this->prepare($theme, 'blog_other_page', true, true);
    }

    #[Test]
    public function post_trail_tags_the_page_with_the_chosen_category(): void
    {
        $category = $this->createStub(\MageOS\Blog\Api\Data\CategoryInterface::class);
        $category->method('getCategoryId')->willReturn(12);
        $category->method('getTitle')->willReturn('News');
        $category->method('getUrlKey')->willReturn('news');
        $post = $this->createStub(PostInterface::class);
        $post->method('getTitle')->willReturn('Hello');

        $picker = $this->createStub(PostCategoryPicker::class);
        $picker->method('forPost')->willReturn($category);

        $theme = $this->createMock(ThemeBreadcrumbs::class);
        $theme->expects(self::exactly(4))->method('addCrumb');

        $block = $this->prepare($theme, 'blog_post_view', true, true, $post, $picker);

        self::assertSame(['mageos_blog_category_12'], $block->getIdentities());
    }

    private function prepare(
        mixed $themeBlock,
        string $action,
        bool $enabled,
        bool $breadcrumbs,
        mixed $registered = null,
        ?PostCategoryPicker $picker = null,
    ): Breadcrumbs {
        $request = $this->createStub(Http::class);
        $request->method('getFullActionName')->willReturn($action);
        $layout = $this->createStub(LayoutInterface::class);
        $layout->method('getBlock')->willReturn($themeBlock);
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getLayout')->willReturn($layout);

        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn($enabled);
        $config->method('isBreadcrumbsEnabled')->willReturn($breadcrumbs);

        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(
            static fn (string $key): mixed => $key === PostViewController::REGISTRY_KEY ? $registered : null
        );

        $block = new Breadcrumbs(
            $context,
            $registry,
            $config,
            $storeManager,
            $this->trailBuilder(),
            $picker ?? $this->createStub(PostCategoryPicker::class)
        );
        $block->setLayout($layout);

        return $block;
    }

    private function trailBuilder(): TrailBuilder
    {
        $urlBuilder = $this->createStub(\Magento\Framework\UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturn('https://shop.test/');

        return new TrailBuilder($urlBuilder);
    }
}

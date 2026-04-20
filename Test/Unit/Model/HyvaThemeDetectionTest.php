<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use MageOS\Blog\Model\HyvaThemeDetection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HyvaThemeDetectionTest extends TestCase
{
    #[Test]
    public function detects_hyva_in_theme_code(): void
    {
        $theme = $this->createMock(ThemeInterface::class);
        $theme->method('getCode')->willReturn('Hyva/default');
        $theme->method('getThemePath')->willReturn('Hyva/default');
        $theme->method('getParentTheme')->willReturn(null);

        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($theme);

        self::assertTrue((new HyvaThemeDetection($design))->execute());
    }

    #[Test]
    public function detects_hyva_via_parent_theme(): void
    {
        $parent = $this->createMock(ThemeInterface::class);
        $parent->method('getCode')->willReturn('Hyva/default');
        $parent->method('getThemePath')->willReturn('Hyva/default');
        $parent->method('getParentTheme')->willReturn(null);

        $child = $this->createMock(ThemeInterface::class);
        $child->method('getCode')->willReturn('Acme/storefront');
        $child->method('getThemePath')->willReturn('Acme/storefront');
        $child->method('getParentTheme')->willReturn($parent);

        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($child);

        self::assertTrue((new HyvaThemeDetection($design))->execute());
    }

    #[Test]
    public function returns_false_for_pure_luma(): void
    {
        $blank = $this->createMock(ThemeInterface::class);
        $blank->method('getCode')->willReturn('Magento/blank');
        $blank->method('getThemePath')->willReturn('Magento/blank');
        $blank->method('getParentTheme')->willReturn(null);

        $luma = $this->createMock(ThemeInterface::class);
        $luma->method('getCode')->willReturn('Magento/luma');
        $luma->method('getThemePath')->willReturn('Magento/luma');
        $luma->method('getParentTheme')->willReturn($blank);

        $design = $this->createMock(DesignInterface::class);
        $design->method('getDesignTheme')->willReturn($luma);

        self::assertFalse((new HyvaThemeDetection($design))->execute());
    }

    #[Test]
    public function result_is_cached_across_calls(): void
    {
        $theme = $this->createMock(ThemeInterface::class);
        $theme->method('getCode')->willReturn('Hyva/default');
        $theme->method('getThemePath')->willReturn('Hyva/default');
        $theme->method('getParentTheme')->willReturn(null);

        $design = $this->createMock(DesignInterface::class);
        $design->expects(self::once())->method('getDesignTheme')->willReturn($theme);

        $detection = new HyvaThemeDetection($design);
        $detection->execute();
        $detection->execute();
    }
}

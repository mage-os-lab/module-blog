<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;
use MageOS\Blog\Api\HyvaThemeDetectionInterface;

class HyvaThemeDetection implements HyvaThemeDetectionInterface
{
    private const HYVA_NEEDLE = 'hyva';

    private ?bool $cached = null;

    public function __construct(private readonly DesignInterface $design)
    {
    }

    public function execute(): bool
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $theme = $this->design->getDesignTheme();
        while ($theme instanceof ThemeInterface) {
            $haystack = strtolower((string) $theme->getCode())
                . ' '
                . strtolower((string) $theme->getThemePath());
            if (str_contains($haystack, self::HYVA_NEEDLE)) {
                return $this->cached = true;
            }
            $parent = $theme->getParentTheme();
            if (!$parent instanceof ThemeInterface || $parent === $theme) {
                break;
            }
            $theme = $parent;
        }

        return $this->cached = false;
    }
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Model;

use MageOS\Blog\Api\HyvaThemeDetectionInterface;
use MageOS\Blog\Model\AbstractThemeDetection;

class HyvaThemeDetection extends AbstractThemeDetection implements HyvaThemeDetectionInterface
{
    /**
     * @return string
     */
    public function getThemeModuleName(): string
    {
        return 'Hyva_Theme';
    }

    /**
     * @return string
     */
    public function getThemeName(): string
    {
        return 'hyva';
    }
}

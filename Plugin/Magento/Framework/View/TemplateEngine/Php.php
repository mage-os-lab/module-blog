<?php

declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as PhpEngine;
use MageOS\Blog\Api\HyvaThemeDetectionInterface;

class Php
{
    private const MODULE_PATH_SEGMENT = '/MageOS/Blog/view/frontend/templates/';

    public function __construct(private readonly HyvaThemeDetectionInterface $hyvaDetection)
    {
    }

    /**
     * @param array<string, mixed> $dictionary
     * @return array{0: BlockInterface, 1: string, 2: array<string, mixed>}
     */
    public function beforeRender(
        PhpEngine $subject,
        BlockInterface $block,
        string $fileName,
        array $dictionary = []
    ): array {
        if (!$this->hyvaDetection->execute()) {
            return [$block, $fileName, $dictionary];
        }

        $position = strpos($fileName, self::MODULE_PATH_SEGMENT);
        if ($position === false) {
            return [$block, $fileName, $dictionary];
        }

        $suffix = substr($fileName, $position + \strlen(self::MODULE_PATH_SEGMENT));
        if (str_starts_with($suffix, 'hyva/')) {
            return [$block, $fileName, $dictionary];
        }

        $hyvaPath = substr($fileName, 0, $position + \strlen(self::MODULE_PATH_SEGMENT)) . 'hyva/' . $suffix;
        if (is_file($hyvaPath)) {
            return [$block, $hyvaPath, $dictionary];
        }

        return [$block, $fileName, $dictionary];
    }
}

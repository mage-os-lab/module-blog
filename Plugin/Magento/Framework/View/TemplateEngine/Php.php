<?php
declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as TemplateEnginePhp;
use MageOS\Blog\Model\HyvaThemeDetection\Proxy as HyvaThemeDetection;

class Php
{
    private HyvaThemeDetection $hyvaThemeDetection;

    public function __construct(
        HyvaThemeDetection $hyvaThemeDetection
    )
    {
        $this->hyvaThemeDetection = $hyvaThemeDetection;
    }

    /**
     * @param TemplateEnginePhp $subject
     * @param BlockInterface $block
     * @param $fileName
     * @param array $dictionary
     * @return array
     */
    public function beforeRender(
        TemplateEnginePhp $subject,
        BlockInterface $block,
                          $fileName,
        array $dictionary = []
    ): array
    {
        $dictionary['hyvaThemeDetection'] = $this->hyvaThemeDetection;

        return [$block, $fileName, $dictionary];
    }
}

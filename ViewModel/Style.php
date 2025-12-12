<?php
declare(strict_types=1);

namespace MageOS\Blog\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Source;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use MageOS\Blog\Model\Config;

class Style implements ArgumentInterface
{
    private Source $source;
    private AssetRepository $assetRepository;
    private Config $config;
    private array $done = [];

    public function __construct(
        Source $source,
        AssetRepository $assetRepository,
        Config $config
    ) {
        $this->source = $source;
        $this->assetRepository = $assetRepository;
        $this->config = $config;
    }

    /**
     * @param $file
     * @return null|string
     * @throws LocalizedException
     */
    public function getStyle($file): ?string
    {
        if ($this->validateFile($file)) {
            return '';
        }

        if (isset($this->done[$file])) {
            return '';
        }
        $this->done[$file] = true;

        if (!str_contains($file, '::')) {
            $file = 'MageOS_Blog::css/' . $file;
        }

        if (!str_contains($file, '.css')) {
            $file = $file . '.css';
        }

        $shortFileName = $file;

        $asset = $this->assetRepository->createAsset($file);

        $fileContent = '';

        $file = $this->source->getFile($asset);
        if (!$file || !file_exists($file)) {
            // @todo check how to better implement this since findRelativeSourceFilePath is deprecated
            $file = $this->source->findRelativeSourceFilePath($asset);
            if ($file && !file_exists($file)) {
                $file = '../' . $file;

            }
        }

        if ($file && file_exists($file)) {
            $fileContent = file_get_contents($file);
        }

        $fileContent = str_replace(
            'url(../',
            ' url(' . dirname($asset->getUrl('')) . '/../',
            $fileContent
        );

        if (!trim($fileContent)) {
            $fileContent = '/* ' .  $shortFileName . '.css is empty */';
        }

        return PHP_EOL . '
        <!-- Start CSS ' . $shortFileName . ' ' . ((int)(strlen($fileContent) / 1024)) . 'Kb -->
        <style>' . $fileContent . '</style>';
    }

    private function fileIsBootstrapCustom($file): bool
    {
        return str_contains($file, Config::CUSTOM_BOOTSTRAP_CSS);
    }

    private function validateFile($file): bool
    {
        return $this->fileIsBootstrapCustom($file) &&
        !$this->config->getIncludeBootstrapCustomMini();
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Plugin\Magento\Framework\View\TemplateEngine;

use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\TemplateEngine\Php as PhpEngine;
use MageOS\Blog\Api\HyvaThemeDetectionInterface;
use MageOS\Blog\Plugin\Magento\Framework\View\TemplateEngine\Php as PhpPlugin;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PhpTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/mageos-blog-plugin-test-' . uniqid();
        mkdir($this->tempDir . '/MageOS/Blog/view/frontend/templates/post', 0o777, true);
        mkdir($this->tempDir . '/MageOS/Blog/view/frontend/templates/hyva/post', 0o777, true);
        file_put_contents($this->tempDir . '/MageOS/Blog/view/frontend/templates/post/view.phtml', '<?php ?>');
        file_put_contents($this->tempDir . '/MageOS/Blog/view/frontend/templates/hyva/post/view.phtml', '<?php ?>');
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tempDir);
    }

    #[Test]
    public function leaves_path_unchanged_when_not_hyva(): void
    {
        $detection = $this->createMock(HyvaThemeDetectionInterface::class);
        $detection->method('execute')->willReturn(false);

        $plugin = new PhpPlugin($detection);
        $path = $this->tempDir . '/MageOS/Blog/view/frontend/templates/post/view.phtml';

        $result = $plugin->beforeRender(
            $this->createMock(PhpEngine::class),
            $this->createMock(BlockInterface::class),
            $path,
            []
        );

        self::assertSame($path, $result[1]);
    }

    #[Test]
    public function swaps_to_hyva_variant_when_file_exists(): void
    {
        $detection = $this->createMock(HyvaThemeDetectionInterface::class);
        $detection->method('execute')->willReturn(true);

        $plugin = new PhpPlugin($detection);
        $path = $this->tempDir . '/MageOS/Blog/view/frontend/templates/post/view.phtml';

        $result = $plugin->beforeRender(
            $this->createMock(PhpEngine::class),
            $this->createMock(BlockInterface::class),
            $path,
            []
        );

        self::assertSame(
            $this->tempDir . '/MageOS/Blog/view/frontend/templates/hyva/post/view.phtml',
            $result[1]
        );
    }

    #[Test]
    public function falls_back_when_hyva_variant_missing(): void
    {
        $detection = $this->createMock(HyvaThemeDetectionInterface::class);
        $detection->method('execute')->willReturn(true);

        $plugin = new PhpPlugin($detection);
        $path = $this->tempDir . '/MageOS/Blog/view/frontend/templates/post/missing.phtml';

        $result = $plugin->beforeRender(
            $this->createMock(PhpEngine::class),
            $this->createMock(BlockInterface::class),
            $path,
            []
        );

        self::assertSame($path, $result[1]);
    }

    #[Test]
    public function leaves_non_module_paths_unchanged(): void
    {
        $detection = $this->createMock(HyvaThemeDetectionInterface::class);
        $detection->method('execute')->willReturn(true);

        $plugin = new PhpPlugin($detection);
        $path = '/var/www/html/vendor/magento/module-catalog/view/frontend/templates/product/view.phtml';

        $result = $plugin->beforeRender(
            $this->createMock(PhpEngine::class),
            $this->createMock(BlockInterface::class),
            $path,
            []
        );

        self::assertSame($path, $result[1]);
    }

    #[Test]
    public function does_not_double_prefix_hyva(): void
    {
        $detection = $this->createMock(HyvaThemeDetectionInterface::class);
        $detection->method('execute')->willReturn(true);

        $plugin = new PhpPlugin($detection);
        $path = $this->tempDir . '/MageOS/Blog/view/frontend/templates/hyva/post/view.phtml';

        $result = $plugin->beforeRender(
            $this->createMock(PhpEngine::class),
            $this->createMock(BlockInterface::class),
            $path,
            []
        );

        self::assertSame($path, $result[1]);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->rrmdir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

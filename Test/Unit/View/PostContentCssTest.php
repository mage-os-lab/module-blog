<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\View;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PostContentCssTest extends TestCase
{
    #[Test]
    public function post_content_wraps_long_unbroken_words(): void
    {
        $css = (string) file_get_contents(__DIR__ . '/../../../view/frontend/web/css/blog.css');

        self::assertMatchesRegularExpression(
            '/\.mageos-blog-post__content\s*\{[^}]*overflow-wrap:\s*break-word;/',
            $css
        );
    }
}

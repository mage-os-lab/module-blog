<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model;

use MageOS\Blog\Model\BlogPostStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BlogPostStatusTest extends TestCase
{
    #[Test]
    public function draft_is_zero(): void
    {
        self::assertSame(0, BlogPostStatus::Draft->value);
    }

    #[Test]
    public function scheduled_is_one(): void
    {
        self::assertSame(1, BlogPostStatus::Scheduled->value);
    }

    #[Test]
    public function published_is_two(): void
    {
        self::assertSame(2, BlogPostStatus::Published->value);
    }

    #[Test]
    public function archived_is_three(): void
    {
        self::assertSame(3, BlogPostStatus::Archived->value);
    }

    #[Test]
    public function can_build_from_int(): void
    {
        self::assertSame(BlogPostStatus::Scheduled, BlogPostStatus::from(1));
    }
}

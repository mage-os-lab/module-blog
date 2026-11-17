<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use MageOS\Blog\Model\Url\UrlRewriteRegenerator;
use MageOS\Blog\Observer\RegenerateUrlRewritesOnPermalinkChange;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RegenerateUrlRewritesOnPermalinkChangeTest extends TestCase
{
    #[Test]
    public function regenerates_only_the_types_whose_segment_changed(): void
    {
        $regenerator = $this->createMock(UrlRewriteRegenerator::class);
        $regenerator->expects(self::exactly(2))
            ->method('regenerate')
            ->willReturnCallback(static function (string $type): array {
                self::assertContains($type, ['category', 'author']);

                return ['rewrites' => 1, 'redirects' => 1, 'errors' => []];
            });
        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects(self::once())->method('addSuccessMessage');

        (new RegenerateUrlRewritesOnPermalinkChange($regenerator, $messages))->execute($this->observer([
            'mageos_blog/permalink/category',
            'mageos_blog/permalink/author',
            'mageos_blog/post/posts_per_page',
        ]));
    }

    #[Test]
    public function does_nothing_when_no_permalink_setting_changed(): void
    {
        $regenerator = $this->createMock(UrlRewriteRegenerator::class);
        $regenerator->expects(self::never())->method('regenerate');
        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects(self::never())->method('addSuccessMessage');

        (new RegenerateUrlRewritesOnPermalinkChange($regenerator, $messages))
            ->execute($this->observer(['mageos_blog/general/enabled']));
    }

    #[Test]
    public function reports_failures_instead_of_success(): void
    {
        $regenerator = $this->createStub(UrlRewriteRegenerator::class);
        $regenerator->method('regenerate')
            ->willReturn(['rewrites' => 0, 'redirects' => 0, 'errors' => ['tag 3: URL key already exists']]);
        $messages = $this->createMock(ManagerInterface::class);
        $messages->expects(self::once())->method('addWarningMessage');
        $messages->expects(self::never())->method('addSuccessMessage');

        (new RegenerateUrlRewritesOnPermalinkChange($regenerator, $messages))
            ->execute($this->observer(['mageos_blog/permalink/tag']));
    }

    /**
     * @param string[] $changedPaths
     */
    private function observer(array $changedPaths): Observer
    {
        return new Observer(['event' => new Event(['changed_paths' => $changedPaths])]);
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Controller\Author;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\Page;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Controller\Author\View;
use MageOS\Blog\Model\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ViewTest extends TestCase
{
    private ResultFactory&MockObject $resultFactory;
    private RequestInterface&MockObject $request;
    private Config&MockObject $config;
    private AuthorRepositoryInterface&MockObject $repository;
    private Registry&MockObject $registry;

    protected function setUp(): void
    {
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->config = $this->createMock(Config::class);
        $this->repository = $this->createMock(AuthorRepositoryInterface::class);
        $this->registry = $this->createMock(Registry::class);
    }

    #[Test]
    public function forwards_to_noroute_without_touching_the_author_when_show_author_is_off(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isShowAuthor')->willReturn(false);
        $this->request->method('getParam')->willReturn('3');
        $this->repository->expects(self::never())->method('getById');
        $this->registry->expects(self::never())->method('register');
        $forward = $this->createMock(Forward::class);
        $forward->expects(self::once())->method('forward')->with('noroute')->willReturnSelf();
        $this->resultFactory->expects(self::once())
            ->method('create')
            ->with(ResultFactory::TYPE_FORWARD)
            ->willReturn($forward);

        self::assertSame($forward, $this->controller()->execute());
    }

    #[Test]
    public function forwards_to_noroute_when_the_module_is_disabled(): void
    {
        $this->config->method('isEnabled')->willReturn(false);
        $this->config->method('isShowAuthor')->willReturn(true);
        $this->repository->expects(self::never())->method('getById');
        $forward = $this->createMock(Forward::class);
        $forward->expects(self::once())->method('forward')->with('noroute')->willReturnSelf();
        $this->resultFactory->method('create')->willReturn($forward);

        self::assertSame($forward, $this->controller()->execute());
    }

    #[Test]
    public function renders_the_author_page_when_show_author_is_on(): void
    {
        $this->config->method('isEnabled')->willReturn(true);
        $this->config->method('isShowAuthor')->willReturn(true);
        $this->request->method('getParam')->with('id')->willReturn('3');
        $author = $this->createStub(AuthorInterface::class);
        $author->method('getIsActive')->willReturn(true);
        $author->method('getName')->willReturn('Jane Doe');
        $this->repository->expects(self::once())->method('getById')->with(3)->willReturn($author);
        $this->registry->expects(self::once())->method('register')->with(View::REGISTRY_KEY, $author);
        $title = $this->createMock(Title::class);
        $title->expects(self::once())->method('set')->with('Jane Doe');
        $pageConfig = $this->createStub(PageConfig::class);
        $pageConfig->method('getTitle')->willReturn($title);
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($pageConfig);
        $this->resultFactory->method('create')->with(ResultFactory::TYPE_PAGE)->willReturn($page);

        self::assertSame($page, $this->controller()->execute());
    }

    private function controller(): View
    {
        return new View(
            $this->resultFactory,
            $this->request,
            $this->config,
            $this->repository,
            $this->registry
        );
    }
}

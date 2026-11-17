<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Controller\Search;

use ErrorException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use MageOS\Blog\Controller\Search\Index;
use MageOS\Blog\Model\Config;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class IndexTest extends TestCase
{
    #[Test]
    public function an_array_valued_q_param_is_treated_as_empty_without_a_conversion_warning(): void
    {
        $config = $this->createStub(Config::class);
        $config->method('isEnabled')->willReturn(true);
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturn(['x']);
        $redirect = $this->createMock(Redirect::class);
        $redirect->expects(self::once())->method('setPath')->with('blog')->willReturnSelf();
        $resultFactory = $this->createStub(ResultFactory::class);
        $resultFactory->method('create')->willReturn($redirect);
        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        }, E_WARNING | E_NOTICE);

        try {
            self::assertSame($redirect, (new Index($resultFactory, $request, $config))->execute());
        } finally {
            restore_error_handler();
        }
    }
}

<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MageOS\Blog\Model\Resolver\AdminAuthorization;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    private const RESOURCE = 'MageOS_Blog::post';

    #[Test]
    public function is_authorized_is_false_for_a_caller_that_is_not_an_admin(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');

        self::assertFalse(
            (new AdminAuthorization($authorization))->isAuthorized($this->context(false), self::RESOURCE)
        );
    }

    #[Test]
    public function is_authorized_is_false_when_the_context_carries_no_extension_attributes(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');
        $context = new class () implements ContextInterface {
        };

        self::assertFalse((new AdminAuthorization($authorization))->isAuthorized($context, self::RESOURCE));
    }

    #[Test]
    public function is_authorized_is_false_when_the_acl_denies_the_resource(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->with(self::RESOURCE)->willReturn(false);

        self::assertFalse(
            (new AdminAuthorization($authorization))->isAuthorized($this->context(true), self::RESOURCE)
        );
    }

    #[Test]
    public function is_authorized_is_true_for_an_admin_the_acl_allows(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->with(self::RESOURCE)->willReturn(true);

        self::assertTrue(
            (new AdminAuthorization($authorization))->isAuthorized($this->context(true), self::RESOURCE)
        );
    }

    #[Test]
    public function is_authorized_recognises_an_admin_by_the_core_user_type(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->with(self::RESOURCE)->willReturn(true);

        self::assertTrue(
            (new AdminAuthorization($authorization))->isAuthorized($this->contextOfType(2), self::RESOURCE)
        );
    }

    #[Test]
    public function is_authorized_rejects_a_customer_user_type(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');

        self::assertFalse(
            (new AdminAuthorization($authorization))->isAuthorized($this->contextOfType(3), self::RESOURCE)
        );
    }

    #[Test]
    public function is_authorized_rejects_a_guest_with_no_user_type(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');

        self::assertFalse(
            (new AdminAuthorization($authorization))->isAuthorized($this->contextOfType(null), self::RESOURCE)
        );
    }

    #[Test]
    public function assert_authorized_demands_an_admin_token_first(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('isAllowed');

        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage('Admin authentication required.');

        (new AdminAuthorization($authorization))->assertAuthorized($this->context(false), self::RESOURCE);
    }

    #[Test]
    public function assert_authorized_names_the_resource_an_admin_is_denied(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(false);

        $this->expectException(GraphQlAuthorizationException::class);
        $this->expectExceptionMessage('Not authorized to access MageOS_Blog::post.');

        (new AdminAuthorization($authorization))->assertAuthorized($this->context(true), self::RESOURCE);
    }

    #[Test]
    public function assert_authorized_lets_an_allowed_admin_through(): void
    {
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn(true);

        (new AdminAuthorization($authorization))->assertAuthorized($this->context(true), self::RESOURCE);

        $this->addToAssertionCount(1);
    }

    private function contextOfType(?int $userType): ContextInterface
    {
        return new class ($userType) implements ContextInterface {
            public function __construct(private readonly ?int $userType)
            {
            }

            public function getUserType(): ?int
            {
                return $this->userType;
            }
        };
    }

    private function context(bool $isAdmin): ContextInterface
    {
        return new class ($isAdmin) implements ContextInterface {
            public function __construct(private readonly bool $isAdmin)
            {
            }

            public function getExtensionAttributes(): object
            {
                return new class ($this->isAdmin) {
                    public function __construct(private readonly bool $isAdmin)
                    {
                    }

                    public function getIsAdmin(): bool
                    {
                        return $this->isAdmin;
                    }
                };
            }
        };
    }
}

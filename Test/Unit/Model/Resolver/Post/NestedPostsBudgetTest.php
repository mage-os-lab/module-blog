<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver\Post;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use MageOS\Blog\Model\Resolver\Post\NestedPostsBudget;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NestedPostsBudgetTest extends TestCase
{
    #[Test]
    public function requests_up_to_the_ceiling_are_allowed(): void
    {
        $budget = new NestedPostsBudget();

        $budget->spend(NestedPostsBudget::MAX_ROWS - 10);
        $budget->spend(10);

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function a_request_past_the_ceiling_is_rejected(): void
    {
        $budget = new NestedPostsBudget();
        $budget->spend(NestedPostsBudget::MAX_ROWS);

        $this->expectException(GraphQlInputException::class);

        $budget->spend(1);
    }

    #[Test]
    public function many_small_nested_calls_add_up(): void
    {
        $budget = new NestedPostsBudget();

        $this->expectException(GraphQlInputException::class);

        for ($i = 0; $i <= NestedPostsBudget::MAX_ROWS; $i++) {
            $budget->spend(1);
        }
    }
}

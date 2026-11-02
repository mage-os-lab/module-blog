<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Collaborators every resolver::resolve() call needs but none of the tests care about.
 */
trait ResolverTestTrait
{
    private function field(): Field
    {
        return $this->createMock(Field::class);
    }

    private function info(): ResolveInfo
    {
        return $this->createMock(ResolveInfo::class);
    }

    private function context(): ContextInterface
    {
        return $this->createMock(ContextInterface::class);
    }
}

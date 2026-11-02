<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MageOS\Blog\Model\Resolver\RelationFilterGuard;
use MageOS\Blog\Model\Resolver\SearchCriteria\FilterKeyNormalizer;

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

    private function normalizer(): FilterKeyNormalizer
    {
        return new FilterKeyNormalizer();
    }

    /**
     * A guard that lets every filter through unchanged.
     */
    private function openGuard(): RelationFilterGuard
    {
        $guard = $this->createMock(RelationFilterGuard::class);
        $guard->method('restrict')->willReturnArgument(1);

        return $guard;
    }
}

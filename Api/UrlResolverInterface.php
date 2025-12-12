<?php
declare(strict_types=1);
namespace MageOS\Blog\Api;

/**
 * Interface UrlResolverInterface
 */
interface UrlResolverInterface
{
    /**
     * @param string $path
     * @return array
     */
    public function resolve($path);
}

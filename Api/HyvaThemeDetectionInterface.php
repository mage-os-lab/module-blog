<?php
declare(strict_types=1);

namespace MageOS\Blog\Api;

interface HyvaThemeDetectionInterface
{
    /**
     * @param $storeId
     * @return bool
     */
    public function execute($storeId = null): bool;
}

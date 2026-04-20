<?php

declare(strict_types=1);

namespace MageOS\Blog\Api;

interface HyvaThemeDetectionInterface
{
    public function execute(): bool;
}

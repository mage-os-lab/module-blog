<?php

declare(strict_types=1);

namespace MageOS\Blog\ViewModel\Search;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Results implements ArgumentInterface
{
    public function __construct(private readonly RequestInterface $request)
    {
    }

    public function getQuery(): string
    {
        return trim((string) $this->request->getParam('q', ''));
    }
}

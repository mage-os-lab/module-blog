<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\Url\Permalink;
use MageOS\Blog\Model\UrlKeyGenerator\CollisionChecker;
use MageOS\Blog\Model\UrlKeyGenerator\SlugNormalizer;

class UrlKeyGenerator implements UrlKeyGeneratorInterface
{
    public function __construct(
        private readonly CollisionChecker $checker,
        private readonly SlugNormalizer $normalizer,
        private readonly Permalink $permalink,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function generate(string $title, string $entityType, ?int $storeId = null): string
    {
        $base = $this->normalizer->normalize($title);
        if ($base === '' || $this->isReserved($base)) {
            throw new \InvalidArgumentException("Cannot generate a URL key from '{$title}'.");
        }

        $candidate = $base;
        $suffix = 1;
        while ($this->checker->isTaken($candidate, $entityType, $storeId)) {
            $suffix++;
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    public function validate(string $urlKey, string $entityType, ?int $storeId, ?int $excludingEntityId = null): void
    {
        if ($this->isReserved($urlKey)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is reserved.");
        }
        if ($this->checker->isTaken($urlKey, $entityType, $storeId, $excludingEntityId)) {
            throw new \InvalidArgumentException("URL key '{$urlKey}' is already in use.");
        }
    }
    /**
     * The fixed reserved words plus every permalink segment configured on any store view.
     *
     * A URL key travels to every store its entity is assigned to, so a segment configured on one
     * store reserves the word everywhere.
     */
    private function isReserved(string $urlKey): bool
    {
        if (\in_array($urlKey, self::RESERVED, true)) {
            return true;
        }

        $storeIds = [null];
        foreach ($this->storeManager->getStores() as $store) {
            $storeIds[] = (int) $store->getId();
        }
        foreach ($storeIds as $storeId) {
            if (\in_array($urlKey, $this->permalink->getSegments($storeId), true)) {
                return true;
            }
        }

        return false;
    }
}

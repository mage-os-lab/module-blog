<?php

declare(strict_types=1);

namespace MageOS\Blog\Test\Unit\Model\Url;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\Permalink;

/**
 * A real Permalink over an in-memory scope config, for tests that need blog paths.
 */
final class PermalinkFixture
{
    /**
     * @param array<string, string> $default config path => value on the default scope
     * @param array<int, array<string, string>> $stores store id => config path => value
     */
    public static function create(array $default = [], array $stores = []): Permalink
    {
        $scopeConfig = new class ($default, $stores) implements ScopeConfigInterface {
            /**
             * @param array<string, string> $default
             * @param array<int, array<string, string>> $stores
             */
            public function __construct(private readonly array $default, private readonly array $stores)
            {
            }

            public function getValue($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
            {
                if ($scopeCode !== null && isset($this->stores[(int) $scopeCode][$path])) {
                    return $this->stores[(int) $scopeCode][$path];
                }

                return $this->default[$path] ?? null;
            }

            public function isSetFlag($path, $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode = null)
            {
                return (bool) $this->getValue($path, $scopeType, $scopeCode);
            }
        };

        return new Permalink(new Config($scopeConfig));
    }
}

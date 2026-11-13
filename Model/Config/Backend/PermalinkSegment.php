<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Route\ConfigInterface as RouteConfig;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use MageOS\Blog\Model\Url\Permalink;

/**
 * Normalises a permalink segment on save and refuses one the storefront cannot serve.
 *
 * Two page types sharing a segment would give a category and a tag with the same URL key the
 * same request path, so a segment must also differ from the other segments saved with it.
 */
class PermalinkSegment extends Value
{
    /**
     * Fields that share the level below the route and must not collide. The route itself sits a
     * level above them, so it may repeat one of them.
     */
    private const SEGMENT_FIELDS = [
        Permalink::SEGMENT_SEARCH,
        Permalink::SEGMENT_CATEGORY,
        Permalink::SEGMENT_TAG,
        Permalink::SEGMENT_AUTHOR,
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        private readonly RouteConfig $routeConfig,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @throws LocalizedException
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->normalize((string) $this->getValue());
        if (preg_match(Permalink::SEGMENT_PATTERN, $value) !== 1) {
            throw new LocalizedException(__(
                'The permalink segment "%1" is invalid. Use lowercase letters, digits, "-" and "_", without "/".',
                $value
            ));
        }

        if ($this->getField() !== Permalink::SEGMENT_ROUTE && \in_array($value, $this->siblingValues(), true)) {
            throw new LocalizedException(__(
                'The permalink segment "%1" is already used by another blog page type.',
                $value
            ));
        }

        if ($this->getField() === Permalink::SEGMENT_ROUTE && $value !== Permalink::ROUTE
            && $this->routeConfig->getRouteByFrontName($value, 'frontend') !== false
        ) {
            throw new LocalizedException(__(
                'The blog route "%1" is already the URL of another storefront module.',
                $value
            ));
        }

        $this->setValue($value);

        return parent::beforeSave();
    }

    /**
     * Values of the other segment fields in the same save, as far as they are in the request.
     *
     * @return string[]
     */
    private function siblingValues(): array
    {
        $own = (string) $this->getField();
        $values = [];
        foreach (self::SEGMENT_FIELDS as $field) {
            if ($field === $own) {
                continue;
            }
            // Null when the field inherits ("Use Default"): compare with the value it inherits.
            $sibling = $this->getFieldsetDataValue($field) ?? $this->_config->getValue(
                'mageos_blog/permalink/' . $field,
                $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $this->getScopeCode()
            );
            $values[] = $this->normalize((string) ($sibling ?? $field));
        }

        return $values;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(trim($value), '/'));
    }
}

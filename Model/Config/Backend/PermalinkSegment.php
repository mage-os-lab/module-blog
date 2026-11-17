<?php

declare(strict_types=1);

namespace MageOS\Blog\Model\Config\Backend;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Model\Url\Permalink;

/**
 * Normalises a permalink segment on save and refuses one the storefront cannot serve.
 *
 * Two page types sharing a segment would give a category and a tag with the same URL key the
 * same request path, so a segment must also differ from the other segments saved with it.
 */
class PermalinkSegment extends Value
{
    private const SEGMENT_FIELDS = [Permalink::SEGMENT_CATEGORY, Permalink::SEGMENT_TAG, Permalink::SEGMENT_AUTHOR];

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

        if (\in_array($value, $this->siblingValues(), true)) {
            throw new LocalizedException(__(
                'The permalink segment "%1" is already used by another blog page type.',
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
        $values = [Permalink::SEGMENT_SEARCH];
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

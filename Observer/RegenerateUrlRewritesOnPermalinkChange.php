<?php

declare(strict_types=1);

namespace MageOS\Blog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use MageOS\Blog\Model\Config;
use MageOS\Blog\Model\Url\UrlRewriteRegenerator;

/**
 * A permalink segment is part of the request path of every page of its type, so saving a new
 * one in the admin rebuilds the rewrites of that type, with redirects from the old URLs.
 *
 * `bin/magento config:set` saves through the same config model in an emulated admin area, so it
 * triggers this too. app:config:import (values from config.php) does not dispatch the event;
 * mageos:blog:url-rewrite:regenerate applies those.
 */
class RegenerateUrlRewritesOnPermalinkChange implements ObserverInterface
{
    /**
     * Config path => entity types whose request paths contain it.
     */
    private const AFFECTED_TYPES = [
        Config::XML_PATH_PERMALINK_CATEGORY => [UrlRewriteRegenerator::TYPE_CATEGORY],
        Config::XML_PATH_PERMALINK_TAG => [UrlRewriteRegenerator::TYPE_TAG],
        Config::XML_PATH_PERMALINK_AUTHOR => [UrlRewriteRegenerator::TYPE_AUTHOR],
    ];

    public function __construct(
        private readonly UrlRewriteRegenerator $regenerator,
        private readonly ManagerInterface $messageManager,
    ) {
    }

    public function execute(Observer $observer): void
    {
        $types = [];
        foreach ((array) $observer->getEvent()->getData('changed_paths') as $path) {
            foreach (self::AFFECTED_TYPES[(string) $path] ?? [] as $type) {
                $types[$type] = $type;
            }
        }
        if ($types === []) {
            return;
        }

        $errors = [];
        foreach ($types as $type) {
            $errors = [...$errors, ...$this->regenerator->regenerate($type)['errors']];
        }

        if ($errors !== []) {
            $this->messageManager->addWarningMessage(__(
                'Some blog URL rewrites could not be regenerated: %1',
                implode('; ', \array_slice($errors, 0, 5))
            ));

            return;
        }

        $this->messageManager->addSuccessMessage(__(
            'Blog URL rewrites were regenerated and the old URLs redirect to the new ones. '
            . 'Flush the full page cache so cached pages link to the new URLs.'
        ));
    }
}

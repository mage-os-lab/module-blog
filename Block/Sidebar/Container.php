<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\View\Element\Template;
use MageOS\Blog\Model\Config;

class Container extends Template
{
    private const WIDGET_ALIASES = [
        'search',
        'recent_posts',
        'category_list',
        'tag_cloud',
        'archive',
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string[]
     */
    public function getWidgetHtml(): array
    {
        $widgets = [];

        foreach (self::WIDGET_ALIASES as $alias) {
            if (!$this->config->isSidebarWidgetEnabled($alias)) {
                continue;
            }

            $child = $this->getChildBlock($alias);
            if ($child === false) {
                continue;
            }

            $widgets[] = [
                'sort_order' => $this->config->getSidebarWidgetSortOrder($alias),
                'html' => $child->toHtml(),
            ];
        }

        usort(
            $widgets,
            static fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']
        );

        return array_column($widgets, 'html');
    }
}

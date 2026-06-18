<?php

declare(strict_types=1);

namespace MageOS\Blog\Block\Sidebar;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Blog\Model\BlogPostStatus;

class Archive extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Template\Context $context,
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager,
        private readonly ResolverInterface $localeResolver,
        private readonly TimezoneInterface $timezone,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array{month: string, label: string, count: int, url: string}>
     */
    public function getMonths(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $postTable = $this->resourceConnection->getTableName('mageos_blog_post');
        $storeTable = $this->resourceConnection->getTableName('mageos_blog_post_store');
        $monthExpression = new \Zend_Db_Expr("DATE_FORMAT(post.publish_date, '%Y-%m')");
        $select = $connection->select()
            ->from(
                ['post' => $postTable],
                [
                    'month' => $monthExpression,
                    'post_count' => new \Zend_Db_Expr('COUNT(DISTINCT post.post_id)'),
                ]
            )
            ->join(
                ['store_link' => $storeTable],
                'store_link.post_id = post.post_id',
                []
            )
            ->where('post.status = ?', BlogPostStatus::Published->value)
            ->where('post.publish_date IS NOT NULL')
            ->where('store_link.store_id IN (?)', [(int) $this->storeManager->getStore()->getId(), 0])
            ->group($monthExpression)
            ->order($monthExpression . ' DESC');

        $months = [];
        foreach ($connection->fetchAll($select) as $row) {
            $month = (string) ($row['month'] ?? '');
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                continue;
            }

            $months[] = [
                'month' => $month,
                'label' => $this->formatMonth($month),
                'count' => (int) ($row['post_count'] ?? 0),
                'url' => $this->getUrl('blog', ['_query' => ['archive' => $month]]),
            ];
        }

        return $months;
    }

    private function formatMonth(string $month): string
    {
        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            $this->timezone->getConfigTimezone() ?: 'UTC',
            \IntlDateFormatter::GREGORIAN,
            'MMMM y'
        );
        $formatted = $formatter->format(new \DateTimeImmutable($month . '-01 12:00:00'));

        return $formatted === false ? $month : ucfirst($formatted);
    }
}

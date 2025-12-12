<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Framework\Model\AbstractModel;

/**
 * @TODO refactor to get rid of Column class
 */
class Statuses extends Column
{
    /**
     * Add to column decorated status
     *
     * @return array
     */
    public function getFrameCallback(): array
    {
        return [$this, 'decorateStatus'];
    }

    /**
     * Decorate status column values
     *
     * @param string $value
     * @param AbstractModel $row
     * @param Column $column
     * @param bool $isExport
     * @return string
     */
    public function decorateStatus(string $value, AbstractModel $row, Column $column, bool $isExport): string
    {
        if ($row->getIsActive() || $row->getStatus()) {
            if ($row->getStatus() == 2) {
                $cell = '<span class="grid-severity-minor"><span>' . $value . '</span></span>';
            } else {
                $cell = '<span class="grid-severity-notice"><span>' . $value . '</span></span>';
            }
        } else {
            $cell = '<span class="grid-severity-critical"><span>' . $value . '</span></span>';
        }
        return $cell;
    }
}

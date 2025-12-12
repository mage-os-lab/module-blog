<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Grid\Column;

use Magento\Backend\Block\Widget\Grid\Column;
use MageOS\Blog\Block\Adminhtml\Grid\Column\Render\Category as RendererCategory;
use MageOS\Blog\Block\Adminhtml\Grid\Column\Filter\Category as FilterCategory;

/**
 * @TODO refactor to get rid of Column class
 */
class Categories extends Column
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_rendererTypes['category'] = RendererCategory::class;
        $this->_filterTypes['category'] = FilterCategory::class;
    }
}

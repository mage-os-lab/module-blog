<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\Grid\Column\Render;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use MageOS\Blog\Model\CategoryFactory;
use MageOS\Blog\Api\Data\BlogCategoryInterface;

class Category extends AbstractRenderer
{
    protected CategoryFactory $categoryFactory;
    protected static array $categories = [];

    public function __construct(
        Context $context,
        CategoryFactory $categoryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Render category grid column
     *
     * @param DataObject $row
     * @return string|null
     */
    public function render(DataObject $row): ?string
    {
        if ($data = $row->getData($this->getColumn()->getIndex())) {
            $titles = [];
            foreach ($data as $id) {
                $title = $this->getCategoryById($id)->getTitle();
                if ($title) {
                    $titles[] = $title;
                }
            }

            return implode(', ', $titles);
        }
        return null;
    }

    /**
     * Retrieve category by id
     *
     * @param $id
     * @return BlogCategoryInterface
     */
    protected function getCategoryById($id)
    {
        if (!isset(self::$categories[$id])) {
            self::$categories[$id] = $this->categoryFactory->create()->load($id);
        }
        return self::$categories[$id];
    }
}

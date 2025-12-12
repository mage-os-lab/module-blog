<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

/**
 * Blog category save controller
 */
class Save extends \MageOS\Blog\Controller\Adminhtml\Category
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::category_save';

    /**
     * After model save
     * @param  \MageOS\Blog\Model\Category $model
     * @param  \Magento\Framework\App\Request\Http $request
     * @return void
     */
    protected function _afterSave($model, $request)
    {
        $model->addData(
            [
                'parent_id' => $model->getParentId(),
                'level' => $model->getLevel(),
            ]
        );
    }

    protected function _beforeSave($model, $request)
    {
        /* Prepare images */
        $this->prepareImagesBeforeSave($model, ['category_img']);
    }

    /**
     * Filter request params
     * @param  array $data
     * @return array
     */
    protected function filterParams($data): array
    {
        /* Prepare dates */
        $dateFilter = $this->_objectManager->create(\Magento\Framework\Stdlib\DateTime\Filter\Date::class);

        $filterRules = [];
        foreach (['custom_theme_from', 'custom_theme_to'] as $dateField) {
            if (!empty($data[$dateField])) {
                $filterRules[$dateField] = $dateFilter;
            }
        }

        $inputFilter = $this->getFilterInput(
            $filterRules,
            [],
            $data
        );

        $data = $inputFilter->getUnescaped();

        return $data;
    }
}

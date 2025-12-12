<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\System\Config\Form\Featured;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\Exception\LocalizedException;
use MageOS\Blog\Block\Adminhtml\System\Config\Form\Featured\Renderer\GridElement;

/**
 * @TODO refactor this class to use the new Generic class
 */
class Form extends Generic
{
    /**
     * @return Generic
     * @throws LocalizedException
     */
    protected function _prepareForm(): Generic
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'post_ids_form',
                    'action' => 'action',
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ]
            ]
        );

        $form->setHtmlIdPrefix('post_ids_');

        $fieldsetGrid = $form->addFieldset(
            'base_fieldset_grid',
            ['label' => __('General Information'), 'class' => 'fieldset-wide']
        );

        $fieldsetGrid->addType(
            'base_field_grid_type',
            GridElement::class
        );

        $fieldsetGrid->addField(
            'base_field_grid',
            'base_field_grid_type',
            [
                'name' => 'base_field_grid',
                'label' => __('Please select post IDs'),
                'title' => __('Please select post IDs')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}

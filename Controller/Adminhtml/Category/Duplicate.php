<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

/**
 * Blog category duplicate controller
 */
class Duplicate extends \MageOS\Blog\Controller\Adminhtml\Category
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::category_save';
}

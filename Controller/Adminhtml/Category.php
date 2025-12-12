<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml;

/**
 * Admin blog category edit controller
 */
class Category extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'blog_category_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'MageOS_Blog::category';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = \MageOS\Blog\Model\Category::class;

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'MageOS_Blog::category';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'is_active';
}

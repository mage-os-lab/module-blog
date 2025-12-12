<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml;

/**
 * Admin blog tag edit controller
 */
class Tag extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'blog_tag_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'MageOS_Blog::tag';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = \MageOS\Blog\Model\Tag::class;

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'MageOS_Blog::tag';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'is_active';
}

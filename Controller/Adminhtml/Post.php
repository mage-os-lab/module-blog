<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml;

/**
 * Admin blog post edit controller
 */
class Post extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'blog_post_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'MageOS_Blog::post';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = \MageOS\Blog\Model\Post::class;

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'MageOS_Blog::post';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'is_active';
}

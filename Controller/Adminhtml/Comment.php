<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml;

/**
 * Admin blog comment edit controller
 */
class Comment extends Actions
{
    /**
     * Form session key
     * @var string
     */
    protected $_formSessionKey  = 'blog_comment_form_data';

    /**
     * Allowed Key
     * @var string
     */
    protected $_allowedKey      = 'MageOS_Blog::comment';

    /**
     * Model class name
     * @var string
     */
    protected $_modelClass      = \MageOS\Blog\Model\Comment::class;

    /**
     * Active menu key
     * @var string
     */
    protected $_activeMenu      = 'MageOS_Blog::comment';

    /**
     * Status field name
     * @var string
     */
    protected $_statusField     = 'status';
}

<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Comment;

/**
 * Blog comment change status controller
 */
class MassStatus extends \MageOS\Blog\Controller\Adminhtml\Comment
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::comment_save';
}

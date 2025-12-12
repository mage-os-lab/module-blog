<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

/**
 * Blog post change status controller
 */
class MassStatus extends \MageOS\Blog\Controller\Adminhtml\Post
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::post_save';
}

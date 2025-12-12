<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

/**
 * Blog post delete controller
 */
class Delete extends \MageOS\Blog\Controller\Adminhtml\Post
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::post_delete';

    /**
     * @TODO Delete image before deleting post. MAKE 100% YOU CAN DELETE THE POST FIRST
     */
}

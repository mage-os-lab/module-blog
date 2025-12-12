<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Post;

/**
 * Class Count increment views_count value
 */
class Viewscount extends View
{

    /**
     * @return $this|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute()
    {
        $post = parent::_initPost();
        if ($post && $post->getId()) {
            $post->getResource()->incrementViewsCount($post);
        }
    }
}

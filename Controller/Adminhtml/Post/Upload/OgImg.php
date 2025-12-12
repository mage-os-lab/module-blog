<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post\Upload;

use MageOS\Blog\Controller\Adminhtml\Upload\Image\Action;

/**
 * Blog featured image upload controller
 */
class OgImg extends Action
{
    /**
     * File key
     *
     * @var string
     */
    protected $_fileKey = 'og_img';

    /**
     * Check admin permissions for this controller
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageOS_Blog::post_save');
    }
}

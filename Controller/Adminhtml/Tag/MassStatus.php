<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

/**
 * Blog tag change status controller
 */
class MassStatus extends \MageOS\Blog\Controller\Adminhtml\Tag
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::tag_save';
}

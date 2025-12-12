<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Comment;

use MageOS\Blog\Model\Comment;

/**
 * Blog comment save controller
 */
class Save extends \MageOS\Blog\Controller\Adminhtml\Comment
{
    /**
     * @var string
     */
    protected $_allowedKey = 'MageOS_Blog::comment_save';

    /**
     * Filter request params
     * @param array $data
     * @return array
     */
    protected function filterParams(array $data): array
    {
        /* Prepare dates */
        $dateFilter = $this->_objectManager->create(\Magento\Framework\Stdlib\DateTime\Filter\DateTime::class);

        $filterRules = [];
        foreach (['creation_time'] as $dateField) {
            if (!empty($data[$dateField])) {
                $filterRules[$dateField] = $dateFilter;
            }
        }

        $inputFilter = $this->getFilterInput(
            $filterRules,
            [],
            $data
        );

        $data = $inputFilter->getUnescaped();

        return $data;
    }
}

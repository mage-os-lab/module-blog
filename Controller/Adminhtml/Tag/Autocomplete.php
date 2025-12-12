<?php
declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Framework\Controller\ResultFactory;

/**
 * Class Tag Ajax Autocomplete
 */
class Autocomplete extends \MageOS\Blog\Controller\Adminhtml\Tag
{
    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $search = (string)$this->getRequest()->getParam('search');
        $collection = $this->_objectManager->create(\MageOS\Blog\Model\Tag\AutocompleteData::class);

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson= $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($collection->getItems($search));
        return $resultJson;
    }
}

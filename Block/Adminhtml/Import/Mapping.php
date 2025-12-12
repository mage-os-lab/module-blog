<?php

namespace MageOS\Blog\Block\Adminhtml\Import;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session;

class Mapping extends Template
{
    protected $session;

    public function __construct(
        Context $context,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->session = $session;
    }

    public function getFormAction()
    {
        return $this->getUrl('blog/import/run');
    }

    public function getCsvData()
    {
        return $this->session->getCsvData() ?: [];
    }
}

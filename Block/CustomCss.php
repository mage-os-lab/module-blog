<?php
declare(strict_types=1);

namespace MageOS\Blog\Block;

class CustomCss extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \MageOS\Blog\Model\Config
     */
    private $config;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \MageOS\Blog\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \MageOS\Blog\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }


    /**
     * Render html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->config->isEnabled()) {
            if ($css = $this->config->getCustomCss()) {
                return '<style>' . $css . '</style>';
            }
        }

        return '';
    }
}

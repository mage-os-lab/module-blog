<?php
declare(strict_types=1);

namespace MageOS\Blog\Block\Adminhtml\System\Config\Form\Featured\Renderer;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use MageOS\Blog\Block\Adminhtml\System\Config\Form\Featured\Grid;
use Magento\Framework\View\LayoutFactory;

class GridElement extends AbstractElement
{
    private LayoutFactory $layoutFactory;

    public function __construct(
        Factory             $factoryElement,
        CollectionFactory   $factoryCollection,
        Escaper             $escaper,
        LayoutFactory       $layoutFactory,
        array               $data = []
    ) {
        $this->layoutFactory = $layoutFactory;
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);
    }

    /**
     * @return string
     */
    public function getElementHtml(): string
    {
        $layout = $this->layoutFactory->create();

        if (!$layout->getBlock('posts.grid')) {
            $layout->createBlock(
                Grid::class,
                'posts.grid'
            );
        }

        return $layout->getBlock('posts.grid')->toHtml();
    }
}

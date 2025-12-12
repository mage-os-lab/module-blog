<?php
declare(strict_types=1);

namespace MageOS\Blog\Plugin\Magento\AdminGws\Model;

use MageOS\Blog\Model\Category;
use MageOS\Blog\Model\Post;

class ModelsPlugin
{
    /**
     * @param $subject
     * @param callable $proceed
     * @param $model
     * @return callable
     */
    public function aroundCmsPageSaveBefore($subject, callable $proceed, $model)
    {
        $isBlogModel = ($model instanceof Post
            || $model instanceof Category
        );
        if ($isBlogModel) {
            if ($model->getStoreIds()) {
                $model->setStoreId($model->getStoreIds());
            }
        }

        return $proceed($model);
    }
}

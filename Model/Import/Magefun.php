<?php
declare(strict_types=1);

namespace MageOS\Blog\Model\Import;

use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Magefun import model
 */
class Magefun extends AbstractImport
{

    public $adapter;
    public $prefix;

    public function execute()
    {
        $this->initGlobalVars();
        $this->checkIntegration();
        $this->importCategories();
        $this->importTags();
        $this->importPost();
        // todo comments
        $this->disconnect();
    }

    private function initGlobalVars(): void
    {
        $this->adapter = $this->getDbAdapter();
        $this->prefix = $this->getPrefix();
    }

    private function checkIntegration(): void
    {
        $sql = 'SELECT * FROM ' . $this->prefix . 'magefan_blog_category LIMIT 1';
        try {
            $this->adapter->query($sql)->execute();
        } catch (\Exception $e) {
            throw new \Exception('Magefun Blog Extension not detected.');
        }
    }

    private function importCategories(): void
    {
        $sql = 'SELECT * FROM ' . $this->prefix . 'magefan_blog_category';
        $result = $this->adapter->query($sql)->execute();
        foreach ($result as $data) {
            $category = $this->_categoryFactory->create();
            try {
                $data['store_ids'] = [$this->getStoreId()];//$this->getCategoryStoreIds($data);
                unset($data['category_id']);
                $category->setData($data)->save();
                $this->_importedCategoriesCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                unset($category);
                $this->_skippedCategories[] = $data['title'];
                $this->_logger->debug('Blog Category Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }
    }

    private function importTags(): void
    {
        $sql = 'SELECT * FROM ' . $this->prefix . 'magefan_blog_tag';
        $result = $this->adapter->query($sql)->execute();
        foreach ($result as $data) {
            try {
                $tag = $this->_tagFactory->create();
                $data['store_ids'] = [$this->getStoreId()];
                unset($data['tag_id']);
                $tag->setData($data)->save();
                $this->_importedTagsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedTags[] = $data['title'];
                $this->_logger->debug('Blog Tag Import [' . $data['title'] . ']: '. $e->getMessage());
            }
        }

    }

    private function importPost(): void
    {
        $sql = 'SELECT * FROM ' . $this->prefix . 'magefan_blog_post';
        $result = $this->adapter->query($sql)->execute();
        foreach ($result as $data) {
            $data = array_merge($data, [
                'store_ids'         => [$this->getStoreId()], //$this->getPostStoreIds($data),
                'categories'        => $this->getPostCategories($data),
                'tags'              => $this->getPostTags($data),
            ]);
            unset($data['post_id']);
            $post = $this->_postFactory->create();
            try {
                $post->setData($data)->save();
                $this->_importedPostsCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_skippedPosts[] = $data['title'];
                $this->_logger->debug('Blog Post Import [' . $data['title'] . ']: '. $e->getMessage());
            }
            unset($post);
        }
    }

    private function disconnect(): void
    {
        $this->adapter->getDriver()->getConnection()->disconnect();
    }

    private function getPostCategories(mixed $data): array
    {
        $postCategories = [];
        $c_sql = 'SELECT category_id FROM ' . $this->prefix .
            'magefan_blog_post_category WHERE post_id = "'.$data['post_id'].'"';
        $c_result = $this->adapter->query($c_sql)->execute();
        foreach ($c_result as $c_data) {
            $postCategories[] = $c_data['category_id'];
        }
        return $postCategories;
    }

    private function getPostTags(mixed $data): array
    {
        $postTags = [];
        $t_sql = 'SELECT tag_id FROM ' . $this->prefix .
            'magefan_blog_post_tag WHERE post_id = "'.$data['post_id'].'"';
        $t_result = $this->adapter->query($t_sql)->execute();
        foreach ($t_result as $t_data) {
            $postTags[] = $t_data['tag_id'];
        }
        return $postTags;
    }

    private function getPostStoreIds(mixed $data): array
    {
        $postStoreIds = [];
        $t_sql = 'SELECT store_id FROM ' . $this->prefix .
            'magefan_blog_post_store WHERE post_id = "'.$data['post_id'].'"';
        $t_result = $this->adapter->query($t_sql)->execute();
        foreach ($t_result as $t_data) {
            $postStoreIds[] = $t_data['store_id'];
        }
        return $postStoreIds;
    }

    private function getCategoryStoreIds(mixed $data): array
    {
        $categoryStoreIds = [];
        $t_sql = 'SELECT store_id FROM ' . $this->prefix .
            'magefan_blog_category_store WHERE category_id = "'.$data['category_id'].'"';
        $t_result = $this->adapter->query($t_sql)->execute();
        foreach ($t_result as $t_data) {
            $categoryStoreIds[] = $t_data['store_id'];
        }
        return $categoryStoreIds;
    }
}

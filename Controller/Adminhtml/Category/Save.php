<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\CategoryRepositoryInterface;
use MageOS\Blog\Api\Data\CategoryInterface;
use MageOS\Blog\Api\Data\CategoryInterfaceFactory;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::category';

    public function __construct(
        Context $context,
        private readonly CategoryRepositoryInterface $repository,
        private readonly CategoryInterfaceFactory $categoryFactory,
        private readonly UrlKeyGeneratorInterface $urlKeyGenerator
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        /** @var Redirect $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $request = $this->getRequest();
        $data = $request instanceof HttpRequest ? (array) $request->getPostValue() : [];

        if ($data === []) {
            return $result->setPath('*/*/');
        }

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : 0;

        try {
            $category = $categoryId > 0
                ? $this->repository->getById($categoryId)
                : $this->categoryFactory->create();

            $this->hydrate($category, $data);
            $saved = $this->repository->save($category);

            $this->messageManager->addSuccessMessage((string) __('Category saved.'));

            if ((int) $this->getRequest()->getParam('back')) {
                return $result->setPath('*/*/edit', ['category_id' => (int) $saved->getCategoryId()]);
            }
            return $result->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('This category no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['category_id' => $categoryId]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                (string) __('Could not save category: %1', $e->getMessage())
            );
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['category_id' => $categoryId]);
        }

        return $result->setPath('*/*/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(CategoryInterface $category, array $data): void
    {
        $scalarFields = [
            'title', 'description',
            'meta_title', 'meta_description', 'meta_keywords',
        ];
        foreach ($scalarFields as $field) {
            if (\array_key_exists($field, $data)) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $category->{$setter}(null);
                } else {
                    $category->{$setter}((string) $value);
                }
            }
        }

        if (isset($data['url_key']) && $data['url_key'] !== '') {
            $category->setUrlKey((string) $data['url_key']);
        } elseif (isset($data['title'])) {
            $category->setUrlKey($this->urlKeyGenerator->generate(
                (string) $data['title'],
                UrlKeyGeneratorInterface::ENTITY_CATEGORY
            ));
        }

        if (\array_key_exists('parent_id', $data)) {
            $parent = $data['parent_id'];
            $category->setParentId(($parent === '' || $parent === null) ? null : (int) $parent);
        }
        if (isset($data['position'])) {
            $category->setPosition((int) $data['position']);
        }

        foreach (['is_active', 'include_in_menu', 'include_in_sidebar'] as $flag) {
            if (\array_key_exists($flag, $data)) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $flag)));
                $category->{$setter}((bool) $data[$flag]);
            }
        }

        $category->setStoreIds($this->parseIdList($data['store_ids'] ?? []));
    }

    /**
     * @return int[]
     */
    private function parseIdList(mixed $raw): array
    {
        if (\is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (!\is_array($raw)) {
            return [];
        }
        return array_values(array_filter(
            array_map('intval', $raw),
            static fn (int $id): bool => $id > 0
        ));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeSession(array $data): void
    {
        $session = $this->_session;
        if (method_exists($session, 'setMageosBlogCategoryFormData')) {
            $session->setMageosBlogCategoryFormData($data);
        }
    }
}

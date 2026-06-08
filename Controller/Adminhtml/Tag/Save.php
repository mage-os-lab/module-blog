<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Tag;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\TagInterface;
use MageOS\Blog\Api\Data\TagInterfaceFactory;
use MageOS\Blog\Api\TagRepositoryInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::tag';

    public function __construct(
        Context $context,
        private readonly TagRepositoryInterface $repository,
        private readonly TagInterfaceFactory $tagFactory,
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

        $tagId = isset($data['tag_id']) ? (int) $data['tag_id'] : 0;

        try {
            $tag = $tagId > 0
                ? $this->repository->getById($tagId)
                : $this->tagFactory->create();

            $this->hydrate($tag, $data);
            $saved = $this->repository->save($tag);

            $this->messageManager->addSuccessMessage((string) __('Tag saved.'));

            if ((int) $this->getRequest()->getParam('back')) {
                return $result->setPath('*/*/edit', ['tag_id' => (int) $saved->getTagId()]);
            }
            return $result->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('This tag no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['tag_id' => $tagId]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not save tag: %1', $e->getMessage()));
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['tag_id' => $tagId]);
        }

        return $result->setPath('*/*/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(TagInterface $tag, array $data): void
    {
        $scalarFields = [
            'title', 'description',
            'meta_title', 'meta_description',
        ];
        foreach ($scalarFields as $field) {
            if (\array_key_exists($field, $data)) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $tag->{$setter}(null);
                } else {
                    $tag->{$setter}((string) $value);
                }
            }
        }

        if (isset($data['url_key']) && $data['url_key'] !== '') {
            $tag->setUrlKey((string) $data['url_key']);
        } elseif (isset($data['title'])) {
            $tag->setUrlKey($this->urlKeyGenerator->generate(
                (string) $data['title'],
                UrlKeyGeneratorInterface::ENTITY_TAG
            ));
        }

        if (\array_key_exists('is_active', $data)) {
            $tag->setIsActive((bool) $data['is_active']);
        }

        $tag->setStoreIds($this->parseStoreIdList($data['store_ids'] ?? []));
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
     * @return int[]
     */
    private function parseStoreIdList(mixed $raw): array
    {
        if (\is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }
        if (!\is_array($raw)) {
            return [0];
        }
        $storeIds = array_values(array_filter(
            array_map('intval', $raw),
            static fn (int $id): bool => $id >= 0
        ));

        return $storeIds === [] ? [0] : $storeIds;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeSession(array $data): void
    {
        $session = $this->_session;
        if (method_exists($session, 'setMageosBlogTagFormData')) {
            $session->setMageosBlogTagFormData($data);
        }
    }
}

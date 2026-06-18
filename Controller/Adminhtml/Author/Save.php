<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Author;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\AuthorRepositoryInterface;
use MageOS\Blog\Api\Data\AuthorInterface;
use MageOS\Blog\Api\Data\AuthorInterfaceFactory;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\ImageUploader;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::author';

    public function __construct(
        Context $context,
        private readonly AuthorRepositoryInterface $repository,
        private readonly AuthorInterfaceFactory $authorFactory,
        private readonly UrlKeyGeneratorInterface $urlKeyGenerator,
        private readonly ImageUploader $imageUploader
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

        $authorId = isset($data['author_id']) ? (int) $data['author_id'] : 0;

        try {
            $author = $authorId > 0
                ? $this->repository->getById($authorId)
                : $this->authorFactory->create();

            $this->hydrate($author, $data);
            $saved = $this->repository->save($author);

            $this->messageManager->addSuccessMessage((string) __('Author saved.'));

            if ((int) $this->getRequest()->getParam('back')) {
                return $result->setPath('*/*/edit', ['author_id' => (int) $saved->getAuthorId()]);
            }
            return $result->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('This author no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['author_id' => $authorId]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not save author: %1', $e->getMessage()));
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['author_id' => $authorId]);
        }

        return $result->setPath('*/*/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(AuthorInterface $author, array $data): void
    {
        $scalarFields = [
            'name', 'bio', 'email',
            'twitter', 'linkedin', 'website',
        ];
        foreach ($scalarFields as $field) {
            if (\array_key_exists($field, $data)) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $author->{$setter}(null);
                } else {
                    $author->{$setter}((string) $value);
                }
            }
        }

        if (isset($data['slug']) && $data['slug'] !== '') {
            $author->setSlug((string) $data['slug']);
        } elseif (isset($data['name'])) {
            $author->setSlug($this->urlKeyGenerator->generate(
                (string) $data['name'],
                UrlKeyGeneratorInterface::ENTITY_AUTHOR
            ));
        }

        if (\array_key_exists('is_active', $data)) {
            $author->setIsActive((bool) $data['is_active']);
        }

        $raw = $data['avatar'] ?? null;
        $fileName = $this->extractUploadedFileName($raw);
        if ($fileName === null) {
            $author->setAvatar(null);
        } else {
            if (\is_array($raw) && isset($raw[0]['tmp_name'])) {
                $fileName = $this->imageUploader->moveFileFromTmp($fileName);
            }
            $author->setAvatar($fileName);
        }
    }

    private function extractUploadedFileName(mixed $raw): ?string
    {
        if (\is_string($raw) && $raw !== '') {
            return $raw;
        }
        if (\is_array($raw) && isset($raw[0]['name'])) {
            return (string) $raw[0]['name'];
        }
        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function storeSession(array $data): void
    {
        $session = $this->_session;
        if (method_exists($session, 'setMageosBlogAuthorFormData')) {
            $session->setMageosBlogAuthorFormData($data);
        }
    }
}

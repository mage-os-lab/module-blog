<?php

declare(strict_types=1);

namespace MageOS\Blog\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Api\UrlKeyGeneratorInterface;
use MageOS\Blog\Model\ImageUploader;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'MageOS_Blog::post';

    public function __construct(
        Context $context,
        private readonly PostRepositoryInterface $repository,
        private readonly PostInterfaceFactory $postFactory,
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

        $postId = isset($data['post_id']) ? (int) $data['post_id'] : 0;

        try {
            $post = $postId > 0
                ? $this->repository->getById($postId)
                : $this->postFactory->create();

            $this->hydrate($post, $data);
            $saved = $this->repository->save($post);

            $this->messageManager->addSuccessMessage((string) __('Post saved.'));

            if ((int) $this->getRequest()->getParam('back')) {
                return $result->setPath('*/*/edit', ['post_id' => (int) $saved->getPostId()]);
            }
            return $result->setPath('*/*/');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage((string) __('This post no longer exists.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['post_id' => $postId]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, (string) __('Could not save post: %1', $e->getMessage()));
            $this->storeSession($data);
            return $result->setPath('*/*/edit', ['post_id' => $postId]);
        }

        return $result->setPath('*/*/');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function hydrate(PostInterface $post, array $data): void
    {
        $scalarFields = [
            'title', 'content', 'short_content',
            'featured_image_alt', 'meta_title', 'meta_description',
            'meta_keywords', 'meta_robots', 'og_title', 'og_description',
            'og_type', 'publish_date',
        ];
        foreach ($scalarFields as $field) {
            if (\array_key_exists($field, $data)) {
                $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
                $value = $data[$field];
                if ($value === '' || $value === null) {
                    $post->{$setter}(null);
                } else {
                    $post->{$setter}((string) $value);
                }
            }
        }

        if (isset($data['url_key']) && $data['url_key'] !== '') {
            $post->setUrlKey((string) $data['url_key']);
        } elseif (isset($data['title'])) {
            $post->setUrlKey($this->urlKeyGenerator->generate(
                (string) $data['title'],
                UrlKeyGeneratorInterface::ENTITY_POST
            ));
        }

        if (isset($data['status'])) {
            $post->setStatus((int) $data['status']);
        }
        if (isset($data['author_id']) && $data['author_id'] !== '') {
            $post->setAuthorId((int) $data['author_id']);
        }

        $post->setStoreIds($this->parseIdList($data['store_ids'] ?? []));
        $post->setCategoryIds($this->parseIdList($data['category_ids'] ?? []));
        $post->setTagIds($this->parseIdList($data['tag_ids'] ?? []));
        $post->setRelatedPostIds($this->parseIdList($data['related_post_ids'] ?? []));
        $post->setRelatedProductIds($this->parseIdList($data['related_product_ids'] ?? []));

        foreach (['featured_image', 'og_image'] as $field) {
            $raw = $data[$field] ?? null;
            $fileName = $this->extractUploadedFileName($raw);
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));
            if ($fileName === null) {
                $post->{$setter}(null);
                continue;
            }
            // If still in tmp dir, move to permanent.
            if (\is_array($raw) && isset($raw[0]['tmp_name'])) {
                $fileName = $this->imageUploader->moveFileFromTmp($fileName);
            }
            $post->{$setter}($fileName);
        }
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
        if (method_exists($session, 'setMageosBlogPostFormData')) {
            $session->setMageosBlogPostFormData($data);
        }
    }
}

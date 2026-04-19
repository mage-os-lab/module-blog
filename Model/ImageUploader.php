<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ImageUploader
{
    private readonly WriteInterface $mediaDirectory;

    /**
     * @param string[] $allowedExtensions
     */
    public function __construct(
        Filesystem $filesystem,
        private readonly UploaderFactory $uploaderFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly string $baseTmpPath,
        private readonly string $basePath,
        private readonly array $allowedExtensions
    ) {
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    public function getBaseTmpPath(): string
    {
        return $this->baseTmpPath;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @return string[]
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Save an uploaded file into the tmp directory.
     *
     *
     * @throws LocalizedException
     * @return array<string, mixed>
     */
    public function saveFileToTmpDir(string $fileId): array
    {
        $baseTmpPath = $this->getBaseTmpPath();

        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $uploader->setAllowCreateFolders(true);

        $result = $uploader->save($this->mediaDirectory->getAbsolutePath($baseTmpPath));
        if (!\is_array($result) || $result === []) {
            throw new LocalizedException(__('File can not be saved to the destination folder.'));
        }

        unset($result['path']);
        $result['tmp_name'] = isset($result['tmp_name']) ? str_replace('\\', '/', (string) $result['tmp_name']) : '';
        $result['url'] = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . $this->buildPath($baseTmpPath, (string) ($result['file'] ?? ''));
        $result['name'] = $result['file'] ?? '';

        return $result;
    }

    /**
     * Move an image from the tmp directory into the permanent base path.
     *
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $baseTmpImagePath = $this->buildPath($this->getBaseTmpPath(), $imageName);
        $baseImagePath = $this->buildPath($this->getBasePath(), $imageName);

        try {
            $this->mediaDirectory->renameFile($baseTmpImagePath, $baseImagePath);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new LocalizedException(__('Something went wrong while saving the file(s).'), $e);
        }

        return $imageName;
    }

    private function buildPath(string $path, string $imageName): string
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }
}

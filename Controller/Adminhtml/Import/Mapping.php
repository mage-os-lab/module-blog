<?php
namespace MageOS\Blog\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv as Reader;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;

class Mapping extends Action
{
    protected $resultPageFactory;
    protected $request;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        Reader $csvReader
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->csvReader = $csvReader;
    }

    /**
     * Start available import execute
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->loadCSV();
        $this->_view->loadLayout();
        $this->_setActiveMenu('MageOS_Blog::import');
        $title = __('CSV Mapping');
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);
        $this->_addBreadcrumb($title, $title);
        $this->_view->renderLayout();
    }

    /**
     * Check is allowed access
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('MageOS_Blog::import');
    }

    private function loadCSV()
    {
        try {
            // 1. Obținem fișierul încărcat
            $uploader = $this->uploaderFactory->create(['fileId' => 'import_file']);
            $uploader->setAllowedExtensions(['csv']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // 2. Mutăm fișierul în var/import/
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $targetPath = $mediaDirectory->getAbsolutePath('import/');
            $result = $uploader->save($targetPath);

            if (!$result) {
                throw new LocalizedException(__('Failed to upload file.'));
            }

            $filePath = $targetPath . $result['file'];

            // 3. Citim fișierul CSV
            $data = $this->csvReader->getData($filePath);

            // 4. Convertim în array asociativ
            $headers = array_shift($data); // Scoatem primul rând (header)
            $csvData = [];

            foreach ($data as $row) {
                $csvData[] = array_combine($headers, $row);
            }

            // 5. Stocăm datele într-o sesiune sau registry pentru a le folosi în block
            $this->_getSession()->setCsvData($csvData);
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong: ') . $e->getMessage());
                $this->_redirect('*/*/');
            }
    }
}

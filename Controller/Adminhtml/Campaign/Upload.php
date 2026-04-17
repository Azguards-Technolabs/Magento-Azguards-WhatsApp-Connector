<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Azguards\WhatsAppConnect\Model\Service\MediaUploadService;

/**
 * Controller to handle media uploads for Campaigns.
 * Senior Level Implementation: Reuses MediaUploadService for consistent DocID generation.
 */
class Upload extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private $uploaderFactory;
    private $filesystem;
    private $logger;
    private MediaUploadService $mediaUploadService;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        MediaUploadService $mediaUploadService,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->mediaUploadService = $mediaUploadService;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        try {
            $fileId = $this->getRequest()->getParam('param_name') ?: 'media_upload';
            
            // Check if file exists in request
            if (!isset($_FILES[$fileId]) && !isset($_FILES['media_upload'])) {
                 // Fallback to manual extraction if standard Magento uploader keys are missing
                 $files = $this->getRequest()->getFiles();
                 $fileId = key($files) ?: 'media_upload';
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'mp4', '3gp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDirectory->getAbsolutePath('tmp/whatsapp_campaigns/');

            $result = $uploader->save($targetPath);

            if (!$result) {
                throw new \Exception('File could not be saved locally.');
            }

            $format = $this->detectFormatByFilename($result['file']);
            if (!$format) {
                throw new \Exception('Unsupported file type for WhatsApp media.');
            }

            // Sync with WhatsApp API via MediaUploadService
            $mediaResult = $this->mediaUploadService->processFileFromTmp([
                ['file' => $result['file']]
            ], $format, 'whatsapp_campaigns');

            if (empty($mediaResult['document_id'])) {
                throw new \Exception('Failed to generate WhatsApp Media Handle.');
            }

            $response = [
                'success' => true,
                'file' => $result['file'],
                'path' => $result['path'],
                'media_handle' => $mediaResult['document_id'],
                // Use local media URL for admin preview to avoid cross-origin/ORB issues with remote preview links
                'url' => $this->getMediaUrl($mediaResult['local_path']),
                'remote_url' => $mediaResult['preview_link'] ?: '',
                'name' => $result['name'],
                'size' => $result['size'],
                'type' => $result['type'],
            ];

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Campaign Media Upload Error: ' . $e->getMessage());
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'success' => false,
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    private function getMediaUrl(string $path): string
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    private function detectFormatByFilename(string $filename): ?string
    {
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) return 'IMAGE';
        if (in_array($extension, ['mp4', '3gp'], true)) return 'VIDEO';
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) return 'DOCUMENT';
        return null;
    }
}

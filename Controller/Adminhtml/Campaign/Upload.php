<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\Service\MediaUploadService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Controller to handle media uploads for Campaigns.
 * Senior Level Implementation: Reuses MediaUploadService for consistent DocID generation.
 */
class Upload extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MediaUploadService
     */
    private MediaUploadService $mediaUploadService;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var File
     */
    private File $fileIo;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param MediaUploadService $mediaUploadService
     * @param StoreManagerInterface $storeManager
     * @param File $fileIo
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger,
        MediaUploadService $mediaUploadService,
        StoreManagerInterface $storeManager,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->mediaUploadService = $mediaUploadService;
        $this->storeManager = $storeManager;
        $this->fileIo = $fileIo;
    }

    /**
     * Upload campaign media and register it with the media service.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $fileId = $this->getRequest()->getParam('param_name') ?: 'media_upload';

            $files = $this->extractFilesPayload();
            if (!isset($files[$fileId]) && !isset($files['media_upload'])) {
                $fileId = array_key_first($files) ?: 'media_upload';
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions([
                'jpg', 'jpeg', 'png', 'mp4', '3gp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'
            ]);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDirectory->getAbsolutePath('tmp/whatsapp_campaigns/');

            $result = $uploader->save($targetPath);
            $this->assertUploadResult($result);

            $format = $this->detectFormatByFilename($result['file']);
            $this->assertFormatDetected($format);

            $mediaResult = $this->mediaUploadService->processFileFromTmp([
                ['file' => $result['file']]
            ], $format, 'whatsapp_campaigns');

            $this->assertMediaHandleGenerated($mediaResult);

            $response = [
                'success' => true,
                'file' => $result['file'],
                'path' => $result['path'],
                'media_handle' => $mediaResult['document_id'],
                // Use a local media URL for admin preview to avoid cross-origin issues with remote preview links.
                'url' => $this->getMediaUrl($mediaResult['local_path']),
                'remote_url' => $mediaResult['preview_link'] ?: '',
                'name' => $result['name'],
                'size' => $result['size'],
                'type' => $result['type'],
            ];

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($response);
        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp Campaign Media Upload Error: ' . $e->getMessage());
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'success' => false,
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    /**
     * Build a public media URL.
     *
     * @param string $path
     * @return string
     */
    private function getMediaUrl(string $path): string
    {
        return $this->storeManager
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }

    /**
     * Detect media format from a file name.
     *
     * @param string $filename
     * @return string|null
     */
    private function detectFormatByFilename(string $filename): ?string
    {
        $fileInfo = $this->fileIo->getPathInfo($filename);
        $extension = strtolower((string)($fileInfo['extension'] ?? ''));
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return 'IMAGE';
        }
        if (in_array($extension, ['mp4', '3gp'], true)) {
            return 'VIDEO';
        }
        if (in_array($extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            return 'DOCUMENT';
        }
        return null;
    }

    /**
     * Extract uploaded files payload.
     *
     * @return array
     */
    private function extractFilesPayload(): array
    {
        $files = $this->getRequest()->getFiles();

        if (is_object($files) && method_exists($files, 'toArray')) {
            $files = $files->toArray();
        }

        return is_array($files) ? $files : [];
    }

    /**
     * Ensure uploader save returned a valid payload.
     *
     * @param array|bool $result
     * @return void
     * @throws LocalizedException
     */
    private function assertUploadResult($result): void
    {
        if (!$result) {
            throw new LocalizedException(__('File could not be saved locally.'));
        }
    }

    /**
     * Ensure a supported media format was detected.
     *
     * @param string|null $format
     * @return void
     * @throws LocalizedException
     */
    private function assertFormatDetected(?string $format): void
    {
        if (!$format) {
            throw new LocalizedException(__('Unsupported file type for WhatsApp media.'));
        }
    }

    /**
     * Ensure the media service returned a handle.
     *
     * @param array $mediaResult
     * @return void
     * @throws LocalizedException
     */
    private function assertMediaHandleGenerated(array $mediaResult): void
    {
        if (empty($mediaResult['document_id'])) {
            throw new LocalizedException(__('Failed to generate WhatsApp Media Handle.'));
        }
    }
}

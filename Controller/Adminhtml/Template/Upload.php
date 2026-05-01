<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

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

class Upload extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

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
     * Upload media and register it with the media service.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $file = $this->getRequiredUploadFile();

            $uploader = $this->uploaderFactory->create(['fileId' => $file]);
            $uploader->setAllowedExtensions([
                'jpg', 'jpeg', 'png', 'mp4', '3gp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'
            ]);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDirectory->getAbsolutePath('tmp/whatsapp_templates/');

            $result = $uploader->save($targetPath);
            $this->assertUploadResult($result);

            $format = $this->detectFormatByFilename($result['file']);
            $this->assertFormatDetected($format);

            $mediaResult = $this->mediaUploadService->processFileFromTmp([
                [
                    'file' => $result['file']
                ]
            ], $format);

            $this->assertDocumentCreated($mediaResult);

            $result['document_id'] = $mediaResult['document_id'];
            $result['preview_link'] = $mediaResult['preview_link'] ?? '';
            $result['type'] = $this->detectMimeTypeByFormat($format);
            // Use a local media URL for admin preview to avoid cross-origin issues with remote preview links.
            $result['url'] = $this->getMediaUrl(
                $mediaResult['local_path'] ?? ('tmp/whatsapp_templates/' . $result['file'])
            );
            $result['remote_url'] = $mediaResult['preview_link'] ?? '';
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);

        } catch (\Throwable $e) {
            $this->logger->error('WhatsApp Template Media Upload Error: ' . $e->getMessage());
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
     * Extract the uploaded file payload and fail if none was provided.
     *
     * @return array
     * @throws LocalizedException
     */
    private function getRequiredUploadFile(): array
    {
        $file = $this->extractUploadFile();
        if (empty($file)) {
            throw new LocalizedException(__('No file uploaded.'));
        }

        return $file;
    }

    /**
     * Extract normalized file data from the request payload.
     *
     * @return array
     */
    private function extractUploadFile(): array
    {
        $files = $this->extractFilesPayload();
        if ($files === []) {
            return [];
        }

        $rootKey = array_key_first($files);
        $fileData = $rootKey !== null ? ($files[$rootKey] ?? null) : null;

        if (!is_array($fileData)) {
            return [];
        }

        if (isset($fileData['tmp_name']) && is_string($fileData['tmp_name'])) {
            return $fileData;
        }

        return $this->flattenNestedFileData($fileData);
    }

    /**
     * Get request file payload as array data.
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
     * Flatten nested file arrays into a single uploaded-file structure.
     *
     * @param array $fileData
     * @return array
     */
    private function flattenNestedFileData(array $fileData): array
    {
        $keys = $this->extractNestedKeys($fileData['tmp_name'] ?? null);
        if (empty($keys)) {
            return [];
        }

        $result = [];
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $attribute) {
            $value = $fileData[$attribute] ?? null;

            foreach ($keys as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) {
                    $value = null;
                    break;
                }
                $value = $value[$key];
            }

            $result[$attribute] = $value;
        }

        return $result;
    }

    /**
     * Resolve the nested key path to the uploaded file values.
     *
     * @param mixed $value
     * @param array $path
     * @return array
     */
    private function extractNestedKeys($value, array $path = []): array
    {
        if (is_string($value)) {
            return $path;
        }

        if (!is_array($value) || $value === []) {
            return [];
        }

        $firstKey = array_key_first($value);
        if ($firstKey === null) {
            return [];
        }

        $path[] = $firstKey;

        return $this->extractNestedKeys($value[$firstKey], $path);
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
     * Map format to a representative MIME type.
     *
     * @param string $format
     * @return string
     */
    private function detectMimeTypeByFormat(string $format): string
    {
        return match ($format) {
            'IMAGE' => 'image/png',
            'VIDEO' => 'video/mp4',
            'DOCUMENT' => 'application/pdf',
            default => 'application/octet-stream'
        };
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
            throw new LocalizedException(__('File can not be saved to the destination folder.'));
        }
    }

    /**
     * Ensure a media format was detected.
     *
     * @param string|null $format
     * @return void
     * @throws LocalizedException
     */
    private function assertFormatDetected(?string $format): void
    {
        if (!$format) {
            throw new LocalizedException(__('Unsupported file type for media registration.'));
        }
    }

    /**
     * Ensure the media-service response contains a document id.
     *
     * @param array $mediaResult
     * @return void
     * @throws LocalizedException
     */
    private function assertDocumentCreated(array $mediaResult): void
    {
        if (empty($mediaResult['document_id'])) {
            throw new LocalizedException(__('Failed to create document in Media Service.'));
        }
    }
}

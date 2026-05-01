<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

class MediaUploadService
{
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $uploaderFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var MediaDocumentService
     */
    private MediaDocumentService $mediaDocumentService;

    /**
     * @var File
     */
    private File $fileIo;

    public const ALLOWED_EXTENSIONS = [
        'IMAGE' => ['jpg', 'jpeg', 'png'],
        'VIDEO' => ['mp4', '3gp'],
        'DOCUMENT' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']
    ];

    /**
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param LoggerInterface $logger
     * @param MediaDocumentService $mediaDocumentService
     * @param File $fileIo
     */
    public function __construct(
        Filesystem $filesystem,
        UploaderFactory $uploaderFactory,
        LoggerInterface $logger,
        MediaDocumentService $mediaDocumentService,
        File $fileIo
    ) {
        $this->filesystem = $filesystem;
        $this->uploaderFactory = $uploaderFactory;
        $this->logger = $logger;
        $this->mediaDocumentService = $mediaDocumentService;
        $this->fileIo = $fileIo;
    }

    /**
     * Move a temp upload into media storage and create its remote media document.
     *
     * @param array $fileData
     * @param string $format
     * @param string $mediaSubdir
     * @return array
     * @throws LocalizedException
     */
    public function processFileFromTmp(
        array $fileData,
        string $format,
        string $mediaSubdir = 'whatsapp_templates'
    ): array {
        try {
            if (empty($fileData[0]['file'])) {
                $this->logger->info('Media Upload: No file found in incoming fileData', [
                    'format' => $format,
                    'media_subdir' => $mediaSubdir
                ]);
                return [];
            }

            $fileName = $fileData[0]['file'];
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $mediaSubdir = trim($mediaSubdir, '/');
            $tmpPath = 'tmp/' . $mediaSubdir . '/' . ltrim($fileName, '/');
            $targetPath = $mediaSubdir . '/' . ltrim($fileName, '/');

            $this->logger->info('Media Upload: Starting tmp file processing', [
                'format' => $format,
                'media_subdir' => $mediaSubdir,
                'file_name' => $fileName,
                'tmp_path' => $tmpPath,
                'target_path' => $targetPath
            ]);

            // Move from tmp to target
            if ($mediaDirectory->isFile($tmpPath)) {
                $mediaDirectory->copyFile($tmpPath, $targetPath);
                $this->logger->info('Media Upload: File copied from tmp to target', [
                    'file_name' => $fileName,
                    'tmp_path' => $tmpPath,
                    'target_path' => $targetPath
                ]);
            } else {
                // Return empty if file not found in tmp, but only if it's not already in target
                if (!$mediaDirectory->isFile($targetPath)) {
                    $this->logger->warning('Media Upload: File missing in both tmp and target locations', [
                        'file_name' => $fileName,
                        'tmp_path' => $tmpPath,
                        'target_path' => $targetPath
                    ]);
                    return [];
                }

                $this->logger->info('Media Upload: File already present in target location', [
                    'file_name' => $fileName,
                    'target_path' => $targetPath
                ]);
            }
            $fileInfo = $this->fileIo->getPathInfo($fileName);
            $fileExtension = strtolower((string)($fileInfo['extension'] ?? ''));
            $contentType = $this->getContentType($format, $fileExtension);

            $this->logger->info('Media Upload: Resolved content type', [
                'file_name' => $fileName,
                'format' => $format,
                'extension' => $fileExtension,
                'content_type' => $contentType
            ]);

            // Call Data Manager Service
            $documentData = $this->mediaDocumentService->createDocument($fileName, $contentType);
            if (!$documentData || empty($documentData['id']) || empty($documentData['preSignLink'])) {
                throw new LocalizedException(__('Failed to create document in Media Service.'));
            }

            $documentId = $documentData['id'];
            $preSignLink = $documentData['preSignLink'];

            $this->logger->info('Media Upload: Document created in media service', [
                'file_name' => $fileName,
                'document_id' => $documentId
            ]);

            // Upload To S3
            $absolutePath = $mediaDirectory->getAbsolutePath($targetPath);
            $uploadSuccess = $this->mediaDocumentService->uploadFileToS3(
                $preSignLink,
                $absolutePath,
                $contentType
            );

            if (!$uploadSuccess) {
                throw new LocalizedException(__('Failed to upload file to S3 storage.'));
            }

            $previewLink = $this->mediaDocumentService->getPreviewLink($documentId);
            if ($previewLink) {
                $this->logger->info('Media Upload: Preview link fetched', [
                    'file_name' => $fileName,
                    'document_id' => $documentId,
                    'preview_link' => $previewLink
                ]);
            } else {
                $this->logger->warning(
                    'Media Upload: Preview link not ready after retries, using local media fallback',
                    [
                        'file_name' => $fileName,
                        'document_id' => $documentId,
                        'local_path' => $targetPath
                    ]
                );
            }

            return [
                'document_id' => $documentId,
                'preview_link' => $previewLink ?: '',
                'local_path' => $targetPath
            ];

        } catch (\Exception $e) {
            $this->logger->error('Media Upload Error: ' . $e->getMessage(), [
                'format' => $format,
                'file_name' => $fileData[0]['file'] ?? null
            ]);
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Resolve content type by media format and extension.
     *
     * @param string $format
     * @param string $extension
     * @return string
     */
    private function getContentType(string $format, string $extension): string
    {
        $types = [
            'IMAGE' => [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png'
            ],
            'VIDEO' => [
                'mp4' => 'video/mp4',
                '3gp' => 'video/3gpp'
            ],
            'DOCUMENT' => [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls' => 'application/vnd.ms-excel',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'ppt' => 'application/vnd.ms-powerpoint',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ]
        ];

        return $types[$format][$extension] ?? 'application/octet-stream';
    }
}

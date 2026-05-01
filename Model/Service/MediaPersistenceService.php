<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class MediaPersistenceService
{
    private const MEDIA_PATH = 'whatsapp_templates';

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var Curl
     */
    private Curl $curl;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var WriteInterface
     */
    private WriteInterface $mediaDirectory;

    /**
     * @var File
     */
    private File $fileIo;

    /**
     * @param Filesystem $filesystem
     * @param Curl $curl
     * @param LoggerInterface $logger
     * @param File $fileIo
     */
    public function __construct(
        Filesystem $filesystem,
        Curl $curl,
        LoggerInterface $logger,
        File $fileIo
    ) {
        $this->filesystem = $filesystem;
        $this->curl = $curl;
        $this->logger = $logger;
        $this->fileIo = $fileIo;
        $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * Persist media from a URL to local storage.
     *
     * @param string $url
     * @param string $fileName
     * @return string|null Relative path from media root
     */
    public function persistFromUrl(string $url, string $fileName): ?string
    {
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $this->logger->info("MediaPersistence: Starting download for $fileName from $url");

        try {
            $this->mediaDirectory->create(self::MEDIA_PATH);

            // Clean filename and ensure extension
            $pathInfo = $this->fileIo->getPathInfo((string)strtok($url, '?'));
            $extension = $pathInfo['extension'] ?? 'jpg';
            $fileName = $this->sanitizeFileName($fileName) . '.' . $extension;
            $relativePath = self::MEDIA_PATH . '/' . $fileName;

            // Check if file already exists
            if ($this->mediaDirectory->isExist($relativePath)) {
                $this->logger->info("MediaPersistence: File already exists at $relativePath");
                return $relativePath;
            }

            $this->logger->info("MediaPersistence: Attempting curl GET for $url");
            $this->curl->setHeaders([]); // Clear any existing headers that third-party file hosts may reject.
            $this->curl->setOption(CURLOPT_FOLLOWLOCATION, true);
            $this->curl->setOption(
                CURLOPT_USERAGENT,
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                . '(KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            );
            $this->curl->setTimeout(15);
            $this->curl->get($url);

            $content = $this->curl->getBody();
            $status = $this->curl->getStatus();
            $this->logger->info(
                "MediaPersistence: Curl completed. Status: $status, Content length: " . strlen((string)$content)
            );

            if ($status !== 200 || empty($content)) {
                $this->logger->warning(
                    "MediaPersistence: Failed to download $fileName. Status: $status. Error Body: "
                    . substr((string)$content, 0, 500)
                );
                return null;
            }

            $this->mediaDirectory->writeFile($relativePath, $content);
            $this->logger->info("MediaPersistence: Successfully saved file to $relativePath");

            return $relativePath;
        } catch (\Exception $e) {
            $this->logger->error("MediaPersistence Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize filename for local storage.
     *
     * @param string $name
     * @return string
     */
    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($name));
    }
}

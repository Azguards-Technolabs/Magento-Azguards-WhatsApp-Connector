<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

class Upload extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $uploaderFactory;
    private $filesystem;
    private $logger;

    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            // Check dynamically which fileId is being uploaded, can be from header or from dynamic rows (carousel)
            $fileId = key($_FILES);

            if (!$fileId) {
                throw new \Exception('No file uploaded.');
            }

            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'mp4', '3gp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $targetPath = $mediaDirectory->getAbsolutePath('tmp/whatsapp_templates/');

            $result = $uploader->save($targetPath);

            if (!$result) {
                throw new \Exception('File can not be saved to the destination folder.');
            }

            // Return URL for preview if needed, or simply the file name to store in hidden input
            $result['url'] = $this->getMediaUrl('tmp/whatsapp_templates/' . $result['file']);
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);

        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Template Media Upload Error: ' . $e->getMessage());
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData([
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode()
            ]);
        }
    }

    private function getMediaUrl(string $path): string
    {
        return $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
    }
}

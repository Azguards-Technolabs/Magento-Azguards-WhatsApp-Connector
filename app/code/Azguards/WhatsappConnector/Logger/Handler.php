<?php
namespace Azguards\WhatsappConnector\Logger;

use Monolog\Handler\StreamHandler;

class Handler extends StreamHandler
{
    protected $loggerType = \Monolog\Logger::INFO;

    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        $filePath = '/var/log/whatsapp_connector.log',
        $level = \Monolog\Logger::INFO,
        $bubble = true,
        $filePermission = null,
        $useLocking = false
    ) {
        parent::__construct(BP . $filePath, $level, $bubble, $filePermission, $useLocking);
    }
}

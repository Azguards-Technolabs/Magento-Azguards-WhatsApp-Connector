<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class WhatsApp extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = 'var/log/whatsapp_connector.log';
}

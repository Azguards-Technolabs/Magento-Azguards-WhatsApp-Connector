<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class WhatsApp extends Base
{
    protected $loggerType = Logger::INFO;
    protected $fileName = 'var/log/whatsapp_connector.log';
}

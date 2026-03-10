<?php

namespace Azguards\WhatsAppConnect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class WhatsApp extends Base
{
    /**
     * @var FileName
     */
    protected $fileName = '/var/log/whatsapp.log';
    /**
     * @var LoggerType
     */
    protected $loggerType = Logger::DEBUG;
}

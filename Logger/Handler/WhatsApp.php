<?php

namespace Azguards\WhatsAppConnect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class WhatsApp extends Base
{
    protected $fileName = '/var/log/whatsapp.log';
    protected $loggerType = Logger::DEBUG;
}

<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class SyncProcess extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = 'var/log/sync_process.log';
}

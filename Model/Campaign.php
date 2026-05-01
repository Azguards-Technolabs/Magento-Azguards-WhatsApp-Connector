<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model;

use Magento\Framework\Model\AbstractModel;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;

class Campaign extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RESCHEDULED = 'rescheduled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SCHEDULED = 'scheduled';

    /**
     * Initialize the campaign model resource.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CampaignResource::class);
    }
}

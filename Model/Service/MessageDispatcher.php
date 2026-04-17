<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\CampaignQueue;
use Azguards\WhatsAppConnect\Model\CampaignQueueFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue as QueueResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory as QueueCollectionFactory;
use Psr\Log\LoggerInterface;

class MessageDispatcher
{
    private CampaignQueueFactory $queueFactory;
    private QueueResource $queueResource;
    private QueueCollectionFactory $queueCollectionFactory;
    private LoggerInterface $logger;

    public function __construct(
        CampaignQueueFactory $queueFactory,
        QueueResource $queueResource,
        QueueCollectionFactory $queueCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->queueFactory = $queueFactory;
        $this->queueResource = $queueResource;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Dispatch a single message immediately (Enqueues it for the worker)
     */
    public function dispatchSingle(
        int $customerId,
        int $templateEntityId,
        array $variableMapping = [],
        ?string $recipientPhone = null
    ): void {
        try {
            /** @var CampaignQueue $queueItem */
            $queueItem = $this->queueFactory->create();
            $queueItem->setData([
                'template_entity_id' => $templateEntityId,
                'customer_id' => $customerId,
                'recipient_phone' => $recipientPhone,
                'variable_mapping' => json_encode($variableMapping),
                'status' => CampaignQueue::STATUS_PENDING
            ]);
            $this->queueResource->save($queueItem);
        } catch (\Exception $e) {
            $this->logger->error('WhatsApp Dispatcher Error: ' . $e->getMessage());
        }
    }

    /**
     * Enqueue items for a bulk campaign
     */
    public function enqueueCampaignItems(Campaign $campaign, array $customers): void
    {
        $campaignId = (int)$campaign->getId();
        $templateEntityId = (int)$campaign->getData('template_entity_id');
        $variableMapping = (string)$campaign->getData('variable_mapping');
        $mediaHandle = (string)$campaign->getData('media_handle');
        $mediaUrl = (string)$campaign->getData('media_url');

        // Get existing customer IDs in queue for this campaign
        $existingQueueCollection = $this->queueCollectionFactory->create();
        $existingQueueCollection->addFieldToFilter('campaign_id', $campaignId);
        
        $enqueuedCustomerIds = [];
        foreach ($existingQueueCollection as $item) {
            $enqueuedCustomerIds[(int)$item->getCustomerId()] = true;
        }

        foreach ($customers as $customer) {
            $customerId = (int)$customer->getId();
            
            // Skip if already in queue
            if (isset($enqueuedCustomerIds[$customerId])) {
                continue;
            }

            try {
                /** @var CampaignQueue $queueItem */
                $queueItem = $this->queueFactory->create();
                $queueItem->setData([
                    'campaign_id' => $campaignId,
                    'template_entity_id' => $templateEntityId,
                    'customer_id' => $customerId,
                    'recipient_phone' => $customer->getData('whatsapp_number') ?: $customer->getData('mobile_number'),
                    'variable_mapping' => $variableMapping,
                    'media_handle' => $mediaHandle,
                    'media_url' => $mediaUrl,
                    'status' => CampaignQueue::STATUS_PENDING
                ]);
                $this->queueResource->save($queueItem);
            } catch (\Exception $e) {
                $this->logger->error("Error enqueuing campaign item for customer $customerId: " . $e->getMessage());
            }
        }
    }
}

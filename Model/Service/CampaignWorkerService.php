<?php

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\CampaignQueue;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue as QueueResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory as QueueCollectionFactory;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CampaignWorkerService
{
    private QueueCollectionFactory $queueCollectionFactory;
    private QueueResource $queueResource;
    private CampaignResource $campaignResource;
    private CampaignService $campaignService;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerDataBuilder $customerDataBuilder;
    private CampaignPlaceholderResolver $placeholderResolver;
    private ApiHelper $apiHelper;
    private TemplateFactory $templateFactory;
    private TemplateResource $templateResource;
    private WhatsAppEventLogger $eventLogger;
    private DateTime $dateTime;

    public function __construct(
        QueueCollectionFactory $queueCollectionFactory,
        QueueResource $queueResource,
        CampaignResource $campaignResource,
        CampaignService $campaignService,
        CustomerRepositoryInterface $customerRepository,
        CustomerDataBuilder $customerDataBuilder,
        CampaignPlaceholderResolver $placeholderResolver,
        ApiHelper $apiHelper,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        WhatsAppEventLogger $eventLogger,
        DateTime $dateTime
    ) {
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->queueResource = $queueResource;
        $this->campaignResource = $campaignResource;
        $this->campaignService = $campaignService;
        $this->customerRepository = $customerRepository;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->placeholderResolver = $placeholderResolver;
        $this->apiHelper = $apiHelper;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->eventLogger = $eventLogger;
        $this->dateTime = $dateTime;
    }

    public function execute(string $triggerSource = 'Cron'): void
    {
        // Fetch pending items from the queue
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('status', CampaignQueue::STATUS_PENDING);
        $collection->setPageSize(50); // Senior Architect: Rule 1 - Batch limits
        $collection->setOrder('id', 'ASC');

        if ($collection->getSize() === 0) {
            $this->eventLogger->logEventTriggered('campaign_worker_idle', [
                'trigger_source' => $triggerSource,
                'message' => 'No pending queue items found.'
            ]);
            return;
        }

        $this->eventLogger->logEventTriggered('campaign_worker_start', [
            'trigger_source' => $triggerSource,
            'item_count' => $collection->getSize()
        ]);

        foreach ($collection as $item) {
            $this->processQueueItem($item, $triggerSource);
        }

        $this->eventLogger->logEventTriggered('campaign_worker_end', [
            'trigger_source' => $triggerSource,
            'message' => 'Batch processing completed.'
        ]);
    }

    private function processQueueItem(CampaignQueue $item, string $triggerSource = 'Cron'): void
    {
        $campaign = null;
        $campaignId = (int)$item->getCampaignId();

        try {
            if ($campaignId > 0) {
                // For campaign items, we still check if campaign is active
                $campaign = $this->campaignService->getById($campaignId);
                if ($campaign->getStatus() !== Campaign::STATUS_PROCESSING) {
                    return;
                }
            }

            // High Efficiency: Read direct from queue row
            $templateId = (int)$item->getTemplateEntityId();
            if (!$templateId && $campaign) {
               $templateId = (int)$campaign->getData('template_entity_id');
            }

            $template = $this->loadTemplate($templateId);
            $customer = $this->customerRepository->getById((int)$item->getCustomerId());
            
            // Use enqueued recipient phone
            $phone = (string)$item->getRecipientPhone();
            $userDetail = $this->customerDataBuilder->buildFromCustomer($customer);
            if ($phone) {
                $userDetail['mobileNumber'] = $phone;
                // Ensure contact id is resolved for the overridden phone only when needed.
                $userDetail['contactId'] = '';
            }

            $eventCode = $campaignId ? 'marketing_campaign_' . $campaignId : 'event_message_' . $templateId;

            // Log item processing start
            $this->eventLogger->logPayload('campaign_worker_item_start', [
                'campaign_id' => $campaignId,
                'customer_id' => $item->getCustomerId(),
                'trigger_source' => $triggerSource,
                'recipient_phone' => $userDetail['mobileNumber']
            ]);

            if (empty($userDetail['mobileNumber'])) {
                throw new \Exception('Missing mobile number');
            }

            // Decode variable mapping from queue row
            $variableMappingRaw = (string)$item->getVariableMapping();
            $variableOverrides = [];
            if ($variableMappingRaw !== '') {
                $decoded = json_decode($variableMappingRaw, true);
                if (is_array($decoded)) {
                    $variableOverrides = $decoded;
                }
            }

            $placeholders = $this->placeholderResolver->build($customer, $userDetail, $template, $variableOverrides);
            
            // Advanced Senior Logic: Fallback to Template's original media if Campaign has no custom override
            $mediaHandleToUse = (string)$item->getMediaHandle() !== '' ? (string)$item->getMediaHandle() : ((string)$template->getHeaderHandle() !== '' ? (string)$template->getHeaderHandle() : null);
            $mediaUrlToUse = (string)$item->getMediaUrl() !== '' ? (string)$item->getMediaUrl() : ((string)$template->getHeaderImage() !== '' ? (string)$template->getHeaderImage() : null);

            // Send Message with Media Overrides or Template Originals
            $response = $this->apiHelper->sendTemplateMessage(
                (string)$template->getTemplateId(),
                $placeholders,
                $userDetail,
                $eventCode,
                $mediaHandleToUse,
                $mediaUrlToUse
            );

            if ($response['success']) {
                $item->setStatus(CampaignQueue::STATUS_SENT);
                if ($campaign) {
                    $campaign->setSentCount((int)$campaign->getSentCount() + 1);
                }
                
                $this->eventLogger->logEventTriggered('campaign_item_success', [
                    'campaign_id' => $campaignId,
                    'customer_id' => $item->getCustomerId(),
                    'mobile' => $userDetail['mobileNumber']
                ]);
            } else {
                throw new \Exception($response['message'] ?? 'Unknown API error');
            }

        } catch (\Exception $e) {
            $item->setStatus(CampaignQueue::STATUS_FAILED);
            $item->setErrorMessage($e->getMessage());
            
            if ($campaign) {
                $campaign->setFailedCount((int)$campaign->getFailedCount() + 1);
            }
        }

        $item->setProcessedAt($this->dateTime->gmtDate());
        $this->queueResource->save($item);

        // Update campaign totals and check for completion
        if (isset($campaign)) {
            $this->checkCampaignCompletion($campaign);
            $this->campaignResource->save($campaign);
        }
    }

    private function checkCampaignCompletion(Campaign $campaign): void
    {
        $collection = $this->queueCollectionFactory->create();
        $collection->addFieldToFilter('campaign_id', $campaign->getId());
        $collection->addFieldToFilter('status', CampaignQueue::STATUS_PENDING);

        if ($collection->getSize() === 0) {
            $campaign->setStatus(Campaign::STATUS_COMPLETED);
            $campaign->setExecutedAt($this->dateTime->gmtDate());
        }
    }

    private function loadTemplate(int $templateId)
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);
        return $template;
    }
}

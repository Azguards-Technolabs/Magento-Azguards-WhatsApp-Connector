<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Campaign;
use Azguards\WhatsAppConnect\Model\CampaignFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign as CampaignResource;
use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Azguards\WhatsAppConnect\Model\TemplateFactory;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template as TemplateResource;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Azguards\WhatsAppConnect\Helper\ApiHelper;

class CampaignService
{
    /**
     * @var CampaignFactory
     */
    private CampaignFactory $campaignFactory;
    /**
     * @var CampaignResource
     */
    private CampaignResource $campaignResource;
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;
    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;
    /**
     * @var Logger
     */
    private Logger $logger;
    /**
     * @var \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory
     */
    private \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory $queueCollectionFactory;
    /**
     * @var \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue
     */
    private \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue $queueResource;
    /**
     * @var WhatsAppEventLogger
     */
    private WhatsAppEventLogger $eventLogger;
    /**
     * @var TemplateFactory
     */
    private TemplateFactory $templateFactory;
    /**
     * @var TemplateResource
     */
    private TemplateResource $templateResource;
    /**
     * @var CustomerCollectionFactory
     */
    private CustomerCollectionFactory $customerCollectionFactory;
    /**
     * @var \Azguards\WhatsAppConnect\Helper\ApiHelper
     */
    private \Azguards\WhatsAppConnect\Helper\ApiHelper $apiHelper;
    /**
     * @var CampaignPlaceholderResolver
     */
    private CampaignPlaceholderResolver $placeholderResolver;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private \Magento\Framework\HTTP\Client\Curl $curl;
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    private \Magento\Framework\Url\DecoderInterface $urlDecoder;

    /**
     *   construct
     *
     * @param CampaignFactory $campaignFactory
     * @param CampaignResource $campaignResource
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $timezone
     * @param Logger $logger
     * @param \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory $queueCollectionFactory
     * @param \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue $queueResource
     * @param WhatsAppEventLogger $eventLogger
     * @param TemplateFactory $templateFactory
     * @param TemplateResource $templateResource
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param \Azguards\WhatsAppConnect\Helper\ApiHelper $apiHelper
     * @param CampaignPlaceholderResolver $placeholderResolver
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     */
    public function __construct(
        CampaignFactory $campaignFactory,
        CampaignResource $campaignResource,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone,
        Logger $logger,
        \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue\CollectionFactory $queueCollectionFactory,
        \Azguards\WhatsAppConnect\Model\ResourceModel\CampaignQueue $queueResource,
        WhatsAppEventLogger $eventLogger,
        TemplateFactory $templateFactory,
        TemplateResource $templateResource,
        CustomerCollectionFactory $customerCollectionFactory,
        \Azguards\WhatsAppConnect\Helper\ApiHelper $apiHelper,
        CampaignPlaceholderResolver $placeholderResolver,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Url\DecoderInterface $urlDecoder
    ) {
        $this->campaignFactory = $campaignFactory;
        $this->campaignResource = $campaignResource;
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
        $this->logger = $logger;
        $this->queueCollectionFactory = $queueCollectionFactory;
        $this->queueResource = $queueResource;
        $this->eventLogger = $eventLogger;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->apiHelper = $apiHelper;
        $this->placeholderResolver = $placeholderResolver;
        $this->curl = $curl;
        $this->urlDecoder = $urlDecoder;
    }

    /**
     * GetById
     *
     * @param int $campaignId
     */
    public function getById(int $campaignId): Campaign
    {
        $campaign = $this->campaignFactory->create();
        $this->campaignResource->load($campaign, $campaignId);
        if (!$campaign->getId()) {
            throw new LocalizedException(__('The campaign with ID "%1" no longer exists.', $campaignId));
        }

        return $campaign;
    }

    /**
     * Save
     *
     * @param array $data
     */
    public function save(array $data): Campaign
    {

        $campaign = !empty($data['entity_id'])
            ? $this->getById((int)$data['entity_id'])
            : $this->campaignFactory->create();
            $scheduleTime = $data['schedule_time'] ?? null;

        $customerGroupIds = $this->normalizeCustomerGroupIds($data['customer_group_ids'] ?? []);
        $targetType = (string)($data['target_type'] ?? 'groups');
        
        // Parse comma-separated string from select2 or existing array
        $customerIds = [];
        if (!empty($data['customer_ids'])) {
            $rawCustIds = is_string($data['customer_ids'])
                ? explode(',', $data['customer_ids'])
                : $data['customer_ids'];
            $customerIds = array_values(array_unique(array_filter(array_map('intval', $rawCustIds))));
        }
        $campaign->addData([
            'campaign_name'       => trim((string)($data['campaign_name'] ?? '')),
            'template_entity_id'  => (int)($data['template_entity_id'] ?? 0),
            'target_type'         => $targetType,
            'customer_group_ids'  => json_encode($customerGroupIds),
            'customer_ids'        => json_encode($customerIds),
            'schedule_time'       => $scheduleTime,
            'executed_at'         => $scheduleTime,
            'media_handle'        => (string)($data['media_handle'] ?? ''),
            'media_url'           => (string)($data['media_url'] ?? ''),
            'status'              => $this->resolveInitialCampaignStatus($campaign, $data),
            'variable_mapping'    => isset($data['variable_mapping']) && is_array($data['variable_mapping'])
                ? json_encode($data['variable_mapping'])
                : (string)($data['variable_mapping'] ?? ''),
            'trigger_type'        => (string)($data['trigger_type'] ?? 'EXPLICIT_DATE'),
            'time_zone'           => (string)($data['time_zone'] ?? 'Asia/Kolkata'),
            'cron_expression'     => (string)($data['cron_expression'] ?? ''),
            'interval_in_months'  => 0,
            'interval_in_weeks'   => 0,
            'interval_in_days'    => 0,
        ]);

        if ($campaign->getData('campaign_name') === '') {
            throw new LocalizedException(__('Campaign Name is required.'));
        }
        if (!(int)$campaign->getData('template_entity_id')) {
            throw new LocalizedException(__('Template is required.'));
        }
        if ($targetType === 'groups' && $customerGroupIds === []) {
            throw new LocalizedException(__('At least one Customer Group is required when Target Type is Groups.'));
        }
        if ($targetType === 'contacts' && $customerIds === []) {
            throw new LocalizedException(__('At least one Contact is required when Target Type is Specific Contacts.'));
        }

        try {
            $existingSchedulerId = (string)($campaign->getData('scheduler_id') ?? '');
            $this->logger->error('=== START CUSTOMER COLLECTION DEBUG ===');
             $this->logger->error('data: ' . json_encode($campaign->getData()));
            $this->logger->error('Target Type: ' . $campaign->getData('target_type'));
            $this->logger->error('Group IDs: ' . $campaign->getData('customer_group_ids'));
            $this->logger->error('Customer IDs: ' . $campaign->getData('customer_ids'));
            
            $customers = $this->getCampaignCustomers($campaign);
            
            $this->logger->error('Total Customers Extracted: ' . count($customers));
            foreach ($customers as $c) {
                $this->logger->error(
                    'CustID: ' . $c->getId() . ' | Mobile: ' . $c->getData('mobile_number')
                    . ' | WA: ' . $c->getData('whatsapp_number')
                );
            }
            
            $template = $this->loadTemplate((int)$campaign->getData('template_entity_id'));
            
            $schedulerResult = $existingSchedulerId !== ''
                ? $this->updateEditedExternalSchedule($campaign, $customers, $template, $existingSchedulerId)
                : $this->createNewExternalSchedule($campaign, $customers, $template);

            $schedulerId = $schedulerResult['scheduler_id'] ?? '';
            if ($schedulerId !== '') {
                $campaign->setData('scheduler_id', $schedulerId);
                if (!empty($schedulerResult['status'])) {
                    $campaign->setData('status', $schedulerResult['status']);
                }
                $this->campaignResource->save($campaign);
            } else {
                throw new LocalizedException(__('API did not return a valid Scheduler ID.'));
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync campaign with external scheduler: ' . $e->getMessage());
            throw new LocalizedException(
                __(
                    'Campaign was aborted because it failed to sync to external WhatTalk scheduler: %1',
                    $e->getMessage()
                )
            );
        }

        return $campaign;
    }

    /**
     * DeleteById
     *
     * @param int $campaignId
     */
    public function deleteById(int $campaignId): void
    {
        $campaign = $this->getById($campaignId);
        $schedulerId = $campaign->getData('scheduler_id');
        if ($schedulerId) {
            try {
                $this->deleteExternalSchedule($schedulerId);
            } catch (\Exception $e) {
                $this->logger->error('Failed to delete external schedule: ' . $e->getMessage());
            }
        }
        $this->campaignResource->delete($campaign);
    }

    /**
     * GetScheduledCampaigns
     */
    public function getScheduledCampaigns(): array
    {
        $now = $this->timezone->date()->format('Y-m-d H:i:s');
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => [
            Campaign::STATUS_PENDING,
            strtoupper(Campaign::STATUS_PENDING),
            Campaign::STATUS_SCHEDULED,
            strtoupper(Campaign::STATUS_SCHEDULED),
            Campaign::STATUS_RESCHEDULED,
            strtoupper(Campaign::STATUS_RESCHEDULED),
        ]]);
        $collection->addFieldToFilter('schedule_time', ['lteq' => $now]);
        $collection->setOrder('schedule_time', 'ASC');

        return $collection->getItems();
    }

    /**
     * MarkProcessing
     *
     * @param Campaign $campaign
     */
    public function markProcessing(Campaign $campaign): void
    {
        $campaign->setData('status', Campaign::STATUS_PROCESSING);
        $this->campaignResource->save($campaign);
    }

    /**
     * MarkCompleted
     *
     * @param Campaign $campaign
     * @param int $sentCount
     * @param int $failedCount
     */
    public function markCompleted(Campaign $campaign, int $sentCount, int $failedCount): void
    {
        $campaign->addData([
            'status' => Campaign::STATUS_COMPLETED,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'executed_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
        $this->campaignResource->save($campaign);
    }

    /**
     * ChangeStatus
     *
     * @param Campaign $campaign
     * @param string $action
     */
    public function changeStatus(Campaign $campaign, string $action): void
    {
        $newStatus = null;
        if ($action === 'pause') {
            $newStatus = Campaign::STATUS_PAUSED;
        } elseif ($action === 'resume') {
            $newStatus = Campaign::STATUS_SCHEDULED;
        }

        if ($newStatus) {
            $campaign->setStatus($newStatus);
            $this->campaignResource->save($campaign);
            
            if ($campaign->getData('scheduler_id')) {
                $this->updateExternalSchedule($campaign);
            }
        }
    }

    /**
     * MarkFailed
     *
     * @param Campaign $campaign
     * @param string $message
     * @param int $sentCount
     * @param int $failedCount
     */
    public function markFailed(Campaign $campaign, string $message, int $sentCount = 0, int $failedCount = 0): void
    {
        $campaign->addData([
            'status' => Campaign::STATUS_FAILED,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'executed_at' => $this->timezone->date()->format('Y-m-d H:i:s'),
            'error_message' => mb_substr($message, 0, 65535),
        ]);
        $this->campaignResource->save($campaign);
        $this->logger->error('Campaign failed: ' . $message);
    }

    /**
     * RetryFailedItems
     *
     * @param Campaign $campaign
     */
    public function retryFailedItems(Campaign $campaign): void
    {
        $queueCollection = $this->queueCollectionFactory->create();
        $queueCollection->addFieldToFilter('campaign_id', $campaign->getId());
        $queueCollection->addFieldToFilter('status', \Azguards\WhatsAppConnect\Model\CampaignQueue::STATUS_FAILED);

        $retryCount = 0;
        foreach ($queueCollection as $item) {
            $item->setStatus(\Azguards\WhatsAppConnect\Model\CampaignQueue::STATUS_PENDING);
            $item->setErrorMessage(null);
            $this->queueResource->save($item);
            $retryCount++;
        }

        if ($retryCount > 0) {
            $campaign->addData([
                'status' => Campaign::STATUS_PROCESSING,
                'failed_count' => (int)$campaign->getFailedCount() - $retryCount,
                'error_message' => null
            ]);
            $this->campaignResource->save($campaign);
            
            $this->eventLogger->logEventTriggered('campaign_retry_triggered', [
                'campaign_id' => $campaign->getId(),
                'retry_count' => $retryCount
            ]);
            
            $this->logger->info(
                sprintf(
                    'Retry triggered for Campaign ID %s. Reseting %s failed items.',
                    $campaign->getId(),
                    $retryCount
                )
            );
        }
    }

    /**
     * @param mixed $value
     * @return int[]
     */
    /**
     * NormalizeCustomerGroupIds
     *
     * @param mixed $value
     */
    private function normalizeCustomerGroupIds($value): array
    {
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = explode(',', $value);
            }
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $groupIds = array_map('intval', $value);
        $groupIds = array_values(array_filter($groupIds, static fn (int $id): bool => $id > 0));

        return array_values(array_unique($groupIds));
    }

    /**
     * GetCampaignCustomers
     *
     * @param Campaign $campaign
     */
    private function getCampaignCustomers(Campaign $campaign): array
    {
        $targetType = $campaign->getData('target_type') ?: 'groups';
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect([
            'entity_id',
            'firstname',
            'lastname',
            'email',
            'whatsapp_phone_number',
            'whatsapp_country_code'
        ]);

        if ($targetType === 'contacts') {
            $customerIds = json_decode((string)$campaign->getData('customer_ids'), true) ?: [];
            $collection->addFieldToFilter('entity_id', ['in' => $customerIds]);
        } else {
            $groupIds = json_decode((string)$campaign->getData('customer_group_ids'), true) ?: [];
            $collection->addFieldToFilter('group_id', ['in' => $groupIds]);
        }

        return array_values($collection->getItems());
    }

    /**
     * LoadTemplate
     *
     * @param int $templateId
     */
    private function loadTemplate(int $templateId)
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);
        return $template;
    }

    /**
     * CreateNewExternalSchedule
     *
     * @param Campaign $campaign
     * @param array $customers
     * @param mixed $template
     */
    private function createNewExternalSchedule(Campaign $campaign, array $customers, $template): array
    {
        $payload = [];
        try {
            $token = $this->apiHelper->getOrRefreshToken();
            $authData = $this->extractAuthData($token);
            $payload = $this->buildSchedulerPayload($campaign, $customers, $template, $authData);
        } catch (\Exception $authEx) {
            $this->logger->error('=== PAYLOAD TEST: WhatsApp Authentication Failed (Host Unreachable) ===');
            $this->logger->error('Error: ' . $authEx->getMessage());
            
            // Build temporary payload just so user can visually verify structure in logs
            $payload = $this->buildSchedulerPayload($campaign, $customers, $template, []);
            $this->logger->error('=== SCHEDULE REQUEST PAYLOAD (DEBUG) ===');
            $this->logger->error(json_encode($payload, JSON_PRETTY_PRINT));
            
            throw new LocalizedException(
                __('Could not authenticate with WhatsApp service. API Base Auth domain might be offline.')
            );
        }

        if (empty($payload)) {
            $this->logger->error('Failed to build payload for scheduler.');
            return [];
        }

        $baseHost = $this->apiHelper->baseUrl();
        $url = $baseHost . '/scheduler-service/api/v1/schedule';
        $headers = $this->getAuthHeaders($token);

        try {
            $curlLog = "curl --location '" . $url . "' \\\n";
            foreach ($this->sanitizeHeadersForLogging($headers) as $k => $v) {
                $curlLog .= "--header '" . $k . ": " . str_replace("'", "'\\''", $v) . "' \\\n";
            }
            $curlLog .= "--data '" . str_replace("'", "'\\''", json_encode($payload, JSON_UNESCAPED_SLASHES)) . "'";

            $this->logger->error("=== WhatsApp External Scheduler Create CURL ===\n" . $curlLog);

            $this->curl->setHeaders($headers);
            $this->curl->post($url, json_encode($payload, JSON_UNESCAPED_SLASHES));

            $responseBody = $this->curl->getBody();
            $response = json_decode($responseBody, true) ?: [];
            $status = $this->curl->getStatus();

            $this->logger->error('=== WhatsApp External Scheduler Response ===');
            $this->logger->error('Status: ' . $status);
            $this->logger->error('Body: ' . $responseBody);

            $jobId = $this->extractExternalSchedulerId($response);
            if ($status >= 200 && $status < 300 && !empty($jobId)) {
                return [
                'scheduler_id' => (string)$jobId,
                'status' => $this->extractExternalStatus($response) ?? (string)($payload['status'] ?? ''),
                ];
            }

            $errorMessage = $response['message'] ?? $response['error'] ?? json_encode($response);
            $this->logger->error('External Scheduler Error Body: ' . json_encode($response));
            throw new LocalizedException(__('External Scheduler returned error: %1', $errorMessage));
        } catch (\Exception $e) {
            $this->logger->error('=== WhatsApp External Scheduler Exception ===');
            $this->logger->error('URL: ' . $url);
            $this->logger->error('Error Message: ' . $e->getMessage());
            
            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('External Scheduler Communication Error: %1', $e->getMessage()));
        }
    }

    /**
     * UpdateEditedExternalSchedule
     *
     * @param Campaign $campaign
     * @param array $customers
     * @param mixed $template
     * @param string $schedulerId
     */
    private function updateEditedExternalSchedule(
        Campaign $campaign,
        array $customers,
        $template,
        string $schedulerId
    ): array {
        $payload = [];
        try {
            $token = $this->apiHelper->getOrRefreshToken();
            $authData = $this->extractAuthData($token);
            $payload = $this->buildSchedulerPayload($campaign, $customers, $template, $authData);
        } catch (\Exception $authEx) {
            $this->logger->error('=== PAYLOAD TEST: WhatsApp Authentication Failed During Scheduler Edit ===');
            $this->logger->error('Error: ' . $authEx->getMessage());

            $payload = $this->buildSchedulerPayload($campaign, $customers, $template, []);
            $this->logger->error('=== SCHEDULE UPDATE REQUEST PAYLOAD (DEBUG) ===');
            $this->logger->error(json_encode($payload, JSON_PRETTY_PRINT));

            throw new LocalizedException(
                __('Could not authenticate with WhatsApp service. API Base Auth domain might be offline.')
            );
        }

        if (empty($payload)) {
            $this->logger->error('Failed to build payload for scheduler update.');
            return [];
        }

        $baseHost = $this->apiHelper->baseUrl();
        $url = $baseHost . '/scheduler-service/api/v1/schedule/' . $schedulerId;
        $headers = $this->getAuthHeaders($token);

        try {
            $curlLog = "curl --location --request PUT '" . $url . "' \\\n";
            foreach ($this->sanitizeHeadersForLogging($headers) as $k => $v) {
                $curlLog .= "--header '" . $k . ": " . str_replace("'", "'\\''", $v) . "' \\\n";
            }
            $curlLog .= "--data '" . str_replace("'", "'\\''", json_encode($payload, JSON_UNESCAPED_SLASHES)) . "'";

            $this->logger->error("=== WhatsApp External Scheduler Edit CURL ===\n" . $curlLog);

            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->curl->post($url, json_encode($payload, JSON_UNESCAPED_SLASHES));

            $responseBody = $this->curl->getBody();
            $response = json_decode($responseBody, true) ?: [];
            $status = $this->curl->getStatus();

            $this->logger->error('=== WhatsApp External Scheduler Edit Response ===');
            $this->logger->error('Status: ' . $status);
            $this->logger->error('Body: ' . $responseBody);

            $updatedJobId = $this->extractExternalSchedulerId($response) ?: $schedulerId;
            if ($status >= 200 && $status < 300) {
                return [
                'scheduler_id' => (string)$updatedJobId,
                'status' => $this->extractExternalStatus($response) ?? (string)($payload['status'] ?? ''),
                ];
            }

            $errorMessage = $response['message'] ?? $response['error'] ?? json_encode($response);
            $this->logger->error('External Scheduler Edit Error Body: ' . json_encode($response));
            throw new LocalizedException(__('External Scheduler returned error: %1', $errorMessage));
        } catch (\Exception $e) {
            $this->logger->error('=== WhatsApp External Scheduler Edit Exception ===');
            $this->logger->error('URL: ' . $url);
            $this->logger->error('Error Message: ' . $e->getMessage());

            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('External Scheduler Communication Error: %1', $e->getMessage()));
        }
    }

    /**
     * UpdateExternalSchedule
     *
     * @param Campaign $campaign
     */
    private function updateExternalSchedule(Campaign $campaign): void
    {
        $schedulerId = $campaign->getData('scheduler_id');
        if (!$schedulerId) {
            return;
        }

        $parsedUrl = \Laminas\Uri\UriFactory::factory($this->apiHelper->baseUrl());
        $baseHost = ($parsedUrl->getScheme() ?: 'https') . '://' . ($parsedUrl->getHost() ?: '');
        $url = $baseHost . '/scheduler-service/api/v1/schedule/' . $schedulerId;
        
        $status = $campaign->getStatus() === Campaign::STATUS_PAUSED ? 'PAUSED' : 'RESUME';
        $payload = ['status' => $status];

        try {
            $token = $this->apiHelper->getOrRefreshToken();
            $headers = $this->getAuthHeaders($token);

            $curlLog = "curl --location --request PUT '" . $url . "' \\\n";
            foreach ($this->sanitizeHeadersForLogging($headers) as $k => $v) {
                $curlLog .= "--header '" . $k . ": " . str_replace("'", "'\\''", $v) . "' \\\n";
            }
            $curlLog .= "--data '" . str_replace("'", "'\\''", json_encode($payload, JSON_UNESCAPED_SLASHES)) . "'";

            $this->logger->error("=== WhatsApp External Scheduler Update CURL ===\n" . $curlLog);

            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->curl->post($url, json_encode($payload));
            
            $httpStatus = $this->curl->getStatus();
            $responseBody = $this->curl->getBody();
            $this->logger->error('Status: ' . $httpStatus);
            $this->logger->error('Body: ' . $responseBody);

            if ($httpStatus >= 400) {
                throw new LocalizedException(__('Status code %1', $httpStatus));
            }
            
            // Sync local status immediately with whatever the API replied
            $response = json_decode($responseBody, true) ?: [];
            $externalStatus = $this->extractExternalStatus($response);
            if ($externalStatus !== null && !$this->statusesMatch((string)$campaign->getStatus(), $externalStatus)) {
                    $campaign->setStatus($externalStatus);
                    $this->campaignResource->save($campaign);
                    $this->logger->info('Updated local campaign status to ' . $externalStatus . ' from API response.');
            }
        } catch (\Exception $e) {
            $this->logger->error('=== WhatsApp External Scheduler Update Exception ===');
            $this->logger->error('URL: ' . $url);
            $this->logger->error('Error Message: ' . $e->getMessage());
            
            if ($e instanceof LocalizedException) {
                throw $e;
            }
            throw new LocalizedException(__('External Scheduler Communication Error: %1', $e->getMessage()));
        }
    }

    /**
     * DeleteExternalSchedule
     *
     * @param string $schedulerId
     */
    private function deleteExternalSchedule(string $schedulerId): void
    {
        $parsedUrl = \Laminas\Uri\UriFactory::factory($this->apiHelper->baseUrl());
        $baseHost = ($parsedUrl->getScheme() ?: 'https') . '://' . ($parsedUrl->getHost() ?: '');
        $url = $baseHost . '/scheduler-service/api/v1/schedule/' . $schedulerId;

        try {
            $token = $this->apiHelper->getOrRefreshToken();
            $headers = $this->getAuthHeaders($token);

            $curlLog = "curl --location --request DELETE '" . $url . "' \\\n";
            foreach ($this->sanitizeHeadersForLogging($headers) as $k => $v) {
                $curlLog .= "--header '" . $k . ": " . str_replace("'", "'\\''", $v) . "' \\\n";
            }
            
            $this->logger->error("=== WhatsApp External Scheduler Delete CURL ===\n" . $curlLog);

            $this->curl->setHeaders($headers);
            $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'DELETE');
            $this->curl->post($url, '');
            
            $httpStatus = $this->curl->getStatus();
            $this->logger->info('Status: ' . $httpStatus);
            $this->logger->info('Body: ' . $this->curl->getBody());
            
            if ($httpStatus >= 400) {
                throw new LocalizedException(__('Status code %1', $httpStatus));
            }
        } catch (\Exception $e) {
            $this->logger->error('=== WhatsApp External Scheduler Delete Exception ===');
            $this->logger->error('URL: ' . $url);
            $this->logger->error('Error Message: ' . $e->getMessage());
            // Intentionally not throwing here to allow local DB delete to continue,
            // since the job might already be deleted or inaccessible on remote.
        }
    }

    /**
     * BuildSchedulerPayload
     *
     * @param Campaign $campaign
     * @param array $customers
     * @param mixed $template
     * @param array $authData
     */
    private function buildSchedulerPayload(Campaign $campaign, array $customers, $template, array $authData): array
    {
        try {
            $triggerType = $campaign->getData('trigger_type') ?: 'EXPLICIT_DATE';
            $selectedTz = $campaign->getData('time_zone');
            $defaultTz = method_exists($this->timezone, 'getConfigTimezone')
                ? $this->timezone->getConfigTimezone()
                : 'UTC';
            $tz = $selectedTz ?: $defaultTz;
            
            $triggerConfig = [
                'trigger_type' => $triggerType,
                'description' => $campaign->getData('campaign_name'),
                'time_zone' => $tz
            ];

            if ($triggerType === 'EXPLICIT_DATE') {
                $dt = new \DateTime($campaign->getData('schedule_time'), new \DateTimeZone('UTC'));
                $triggerConfig['time_zone'] = $tz;
                $triggerConfig['execution_date_time'] = $dt->format('Y-m-d\TH:i:s\Z');
            } else {
                $triggerConfig['cron_expression'] = $campaign->getData('cron_expression');
            }

            $contactNumbers = [];
            foreach ($customers as $customer) {
                $this->logger->error('--- Contact Debug ---');
                $this->logger->error('Customer ID: ' . $customer->getId());
                $this->logger->error(
                    'whatsapp_phone_number raw: ' . var_export($customer->getData('whatsapp_phone_number'), true)
                );
                $this->logger->error(
                    'whatsapp_country_code raw: ' . var_export($customer->getData('whatsapp_country_code'), true)
                );

                $phoneNumber = preg_replace('/\D+/', '', (string)$customer->getData('whatsapp_phone_number'));
                $countryCode = preg_replace('/\D+/', '', (string)$customer->getData('whatsapp_country_code'));
                
                // Build full number: country code + local number
                if ($phoneNumber) {
                    if ($countryCode) {
                        $phone = $countryCode . $phoneNumber;
                    } elseif (strlen($phoneNumber) === 10 && is_numeric($phoneNumber)) {
                        // Default to India (+91) if no country code and 10-digit
                        $phone = '91' . $phoneNumber;
                    } else {
                        $phone = ltrim($phoneNumber, '+');
                    }

                    $this->logger->error('Resolved phone: ' . $phone);
                    $contactNumbers[$phone] = trim($customer->getFirstname() . ' ' . $customer->getLastname());
                } else {
                    $this->logger->error('SKIPPED: whatsapp_phone_number empty for customer ' . $customer->getId());
                }
            }
            $this->logger->error('Final contactNumbers: ' . json_encode($contactNumbers));

            $variableMapping = $campaign->getData('variable_mapping');
            $decodedMapping = is_string($variableMapping) ? json_decode($variableMapping, true) : $variableMapping;
            
            $firstCustomer = reset($customers);
            if (!$firstCustomer instanceof \Magento\Framework\DataObject) {
                $placeholdersData = [];
            } else {
                $placeholdersData = $this->placeholderResolver->build(
                    $firstCustomer,
                    [],
                    $template,
                    $decodedMapping ?: []
                );
            }

            $bodyPlaceholders = [];
            $headerPlaceholders = [];
            $bodyOrder = 1;

            foreach ($placeholdersData as $key => $val) {
                $isHeader = ($key === 'header_image'
                    || $key === 'header_document'
                    || $key === 'header_video'
                    || $key === 'header_text');
                
                if ($isHeader) {
                    $headerPlaceholders[] = [
                        'key' => '1',
                        'value' => (string)$val,
                        'is_user_attribute' => false,
                        'attribute_name' => str_replace('header_', '', (string)$key)
                    ];
                } else {
                    $bodyPlaceholders[] = [
                        'key' => (string)$bodyOrder++,
                        'value' => (string)$val,
                        'is_user_attribute' => false,
                        'attribute_name' => (string)$key
                    ];
                }
            }

            $attributes = [];
            if (!empty($headerPlaceholders)) {
                $attributes['header'] = [
                    'order' => 0,
                    'placeholders' => $headerPlaceholders
                ];
            }
            if (!empty($bodyPlaceholders)) {
                $attributes['body'] = [
                    'order' => 1,
                    'placeholders' => $bodyPlaceholders
                ];
            }
            $status = 'SCHEDULED';
            if (!empty($campaign->getEntityId())) {
                $status = 'RESCHEDULED';
            }
            return [
                'status' => $status,
                'trigger_config' => $triggerConfig,
                'job_data' => [
                    'userId' => $authData['userId'] ?? '',
                    'businessId' => $authData['businessId'] ?? '',
                    'templateId' => $template->getData('template_id'),
                    'contactNumber' => empty($contactNumbers) ? new \stdClass() : (object)$contactNumbers,
                    'attributes' => empty($attributes) ? new \stdClass() : (object)$attributes
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error building scheduler payload: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * SyncExternalAll
     */
    public function syncExternalAll(): array
    {
        $token = $this->apiHelper->getOrRefreshToken();
        $headers = $this->getAuthHeaders($token);
        
        $campaignCollection = $this->collectionFactory->create();
        $campaignCollection->addFieldToFilter('scheduler_id', ['notnull' => true]);
        $campaignCollection->addFieldToFilter('status', ['nin' => [
            Campaign::STATUS_COMPLETED,
            strtoupper(Campaign::STATUS_COMPLETED),
            Campaign::STATUS_FAILED,
            strtoupper(Campaign::STATUS_FAILED),
        ]]);

        $syncedCount = 0;
        
        foreach ($campaignCollection as $campaign) {
            $externalId = $campaign->getData('scheduler_id');
            $url = $this->apiHelper->baseUrl() . '/scheduler-service/api/v1/schedule/' . $externalId;
            
            try {
                $this->logger->info('=== WhatsApp External Scheduler Sync Single Job ===');
                $this->logger->info('URL: ' . $url);
                
                $this->curl->setHeaders($headers);
                $this->curl->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
                $this->curl->get($url);
                
                $responseBody = $this->curl->getBody();
                $response = json_decode($responseBody, true) ?: [];
                $status = $this->curl->getStatus();
                if ($status >= 200 && $status < 300) {
                    $externalStatus = $this->extractExternalStatus($response);
                    
                    if ($externalStatus) {
                        $oldStatus = $campaign->getStatus();
                        if (!$this->statusesMatch((string)$oldStatus, $externalStatus)) {
                            $campaign->setStatus($externalStatus);
                            $this->campaignResource->save($campaign);
                            $syncedCount++;
                            $this->logger->info(
                                sprintf('Campaign %s synced: %s -> %s', $campaign->getId(), $oldStatus, $externalStatus)
                            );
                        }
                    }
                } else {
                    $this->logger->error(
                        sprintf('Failed to sync campaign %s. API returned status %s', $campaign->getId(), $status)
                    );
                }
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Exception while syncing campaign %s: %s', $campaign->getId(), $e->getMessage())
                );
            }
        }
        $this->logger->info(sprintf('Successfully synced %s campaigns.', $syncedCount));
        return ['synced_count' => $syncedCount];
    }

    /**
     * ResolveInitialCampaignStatus
     *
     * @param Campaign $campaign
     * @param array $data
     */
    private function resolveInitialCampaignStatus(Campaign $campaign, array $data): string
    {
        $existingStatus = (string)$campaign->getData('status');
        if ($existingStatus !== '') {
            return $existingStatus;
        }

        return '';
    }

    /**
     * ExtractExternalSchedulerId
     *
     * @param array $response
     */
    private function extractExternalSchedulerId(array $response): ?string
    {
        $schedulerId = $response['id']
            ?? $response['result']['id']
            ?? $response['data']['id']
            ?? $response['result']['data']['id']
            ?? null;

        return $schedulerId !== null && $schedulerId !== '' ? (string)$schedulerId : null;
    }

    /**
     * ExtractExternalStatus
     *
     * @param array $response
     */
    private function extractExternalStatus(array $response): ?string
    {
        $status = $response['status']
            ?? $response['result']['status']
            ?? $response['data']['status']
            ?? $response['result']['data']['status']
            ?? null;

        return is_string($status) && $status !== '' ? $status : null;
    }

    /**
     * StatusesMatch
     *
     * @param string $left
     * @param string $right
     */
    private function statusesMatch(string $left, string $right): bool
    {
        return strtoupper($left) === strtoupper($right);
    }

    /**
     * SanitizeHeadersForLogging
     *
     * @param array $headers
     */
    private function sanitizeHeadersForLogging(array $headers): array
    {
        $sanitized = [];

        foreach ($headers as $key => $value) {
            if (strtolower((string)$key) === 'authorization') {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * ExtractAuthData
     *
     * @param string $token
     */
    private function extractAuthData(string $token): array
    {
        $parts = explode('.', $token);
        $userId = '';
        $businessId = '';

        if (count($parts) === 3) {
            $payload = json_decode($this->urlDecoder->decode($parts[1]), true);
            $userId = $payload['sub'] ?? '';
            $businessId = $payload['active_tenant']['tenant_id'] ?? '';
        }

        return [
            'userId' => $userId,
            'businessId' => $businessId
        ];
    }

    /**
     * GetAuthHeaders
     *
     * @param string $token
     */
    private function getAuthHeaders(string $token): array
    {
        $authData = $this->extractAuthData($token);

        return [
            'userId' => $authData['userId'],
            'businessId' => $authData['businessId'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ];
    }
}

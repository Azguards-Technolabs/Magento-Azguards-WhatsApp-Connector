<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Observer;

use Azguards\WhatsAppConnect\Logger\Logger;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppEventLogger;
use Azguards\WhatsAppConnect\Model\Service\WhatsAppNotificationService;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Service\CustomerDataBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

class CustomerSaveAfter implements ObserverInterface
{
    private WhatsAppNotificationService $notificationService;
    private WhatsAppEventLogger $eventLogger;
    private Registry $registry;
    private Logger $logger;
    private ApiHelper $apiHelper;
    private CustomerDataBuilder $customerDataBuilder;

    public function __construct(
        WhatsAppNotificationService $notificationService,
        WhatsAppEventLogger $eventLogger,
        Registry $registry,
        Logger $logger,
        ApiHelper $apiHelper,
        CustomerDataBuilder $customerDataBuilder
    ) {
        $this->notificationService = $notificationService;
        $this->eventLogger = $eventLogger;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->customerDataBuilder = $customerDataBuilder;
    }

    public function execute(Observer $observer)
    {
        try {
            $this->logger->info('CustomerSaveAfter observer invoked.');
            $customer = $observer->getEvent()->getCustomer();
            if (!$customer) {
                $this->logger->warning('CustomerSaveAfter observer invoked without a customer instance.');
                return;
            }

            $isNewCustomer = method_exists($customer, 'isObjectNew') && $customer->isObjectNew();
            if (!$isNewCustomer && method_exists($customer, 'getOrigData')) {
                $isNewCustomer = !$customer->getOrigData('entity_id');
            }

            if (!$isNewCustomer) {
                return;
            }

            if ($this->registry->registry('customer_save_event')) {
                return;
            }
            $this->registry->register('customer_save_event', '1');

            $this->logger->info(sprintf(
                'CustomerSaveAfter processing new customer. customer_id=%s email=%s',
                (string)$customer->getEntityId(),
                (string)$customer->getEmail()
            ));

            if (!(bool)$this->apiHelper->getConfigValue(EventConfig::MODULE_ENABLED)) {
                return;
            }

            // Contact sync is allowed here (customer create) and should not happen during message send.
            $userDetail = $this->customerDataBuilder->buildFromCustomer($customer);
            if (empty($userDetail['contactId'])) {
                $sync = $this->apiHelper->syncWhatsTalkUser($userDetail, 'customer_create', (int)$customer->getEntityId());
                if (empty($sync['success'])) {
                    $this->logger->warning('CustomerSaveAfter contact sync failed: ' . (string)($sync['message'] ?? ''));
                    return;
                }
                if (!empty($sync['contact_id'])) {
                    $userDetail['contactId'] = (string)$sync['contact_id'];
                    if (method_exists($customer, 'setData')) {
                        $customer->setData('whatsapp_contact_id', (string)$sync['contact_id']);
                    }
                }
            }

            $response = $this->notificationService->notifyCustomerRegistration($customer, $userDetail);

            $this->logger->info(sprintf(
                'CustomerSaveAfter notifyCustomerRegistration completed. customer_id=%s success=%s message=%s',
                (string)$customer->getEntityId(),
                !empty($response['success']) ? 'true' : 'false',
                (string)($response['message'] ?? '')
            ));
        } catch (\Throwable $e) {
            $this->eventLogger->logError(EventConfig::CUSTOMER_REGISTRATION, $e->getMessage());
            $this->logger->error('Error in CustomerSaveAfter Observer: ' . $e->getTraceAsString());
            $this->logger->error('Error in CustomerSaveAfter Observer Error message: ' . $e->getMessage());
        }
    }
}

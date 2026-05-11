<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Logger\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Azguards\WhatsAppConnect\Api\RecipientResolverInterface;

class CustomerDataBuilder
{
    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var RecipientResolverInterface
     */
    private RecipientResolverInterface $recipientResolver;

    /**
     * @param ApiHelper $apiHelper
     * @param StoreManagerInterface $storeManager
     * @param Logger $logger
     * @param RecipientResolverInterface $recipientResolver
     */
    public function __construct(
        ApiHelper $apiHelper,
        StoreManagerInterface $storeManager,
        Logger $logger,
        RecipientResolverInterface $recipientResolver
    ) {
        $this->apiHelper = $apiHelper;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->recipientResolver = $recipientResolver;
    }

    /**
     * Build recipient data from an order.
     *
     * @param OrderInterface $order
     * @return array
     */
    public function buildFromOrder(OrderInterface $order): array
    {
        $userDetail = $this->apiHelper->getUserDetailData($order);
        $phone = $this->recipientResolver->resolveByEntity($order);
        if ($phone) {
            $userDetail['mobileNumber'] = preg_replace('/\D/', '', $phone);
        }

        $userDetail['order'] = [
            'increment_id' => (string)$order->getIncrementId(),
            'status' => (string)$order->getStatus(),
            'grand_total' => (string)$order->getGrandTotal(),
        ];

        return $userDetail;
    }

    /**
     * Build recipient details from a quote.
     *
     * @param CartInterface $quote
     * @return array
     *
     * Abandoned cart messages should never send to fabricated placeholder numbers; require a real phone.
     */
    public function buildFromQuote(CartInterface $quote): array
    {
        $billing = $quote->getBillingAddress();
        $countryId = $billing ? (string)$billing->getCountryId() : '';
        $telephone = $this->recipientResolver->resolveByEntity($quote);
        $email = (string)$quote->getCustomerEmail();
        $firstName = $billing ? (string)$billing->getFirstname() : (string)$quote->getCustomerFirstname();
        $lastName = $billing ? (string)$billing->getLastname() : (string)$quote->getCustomerLastname();

        $countryCode = $this->apiHelper->getCountryCallingCodes($countryId ?: 'IN');
        $countryCode = preg_replace('/\D/', '', (string)$countryCode);
        $telephone = preg_replace('/\D/', '', (string)$telephone);

        $store = $this->storeManager->getStore((int)$quote->getStoreId());
        $userDetail = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'countryCode' => $countryCode,
            'mobileNumber' => $telephone,
            'contactId' => '',
            'imageURL' => '',
            'email' => $email,
            'businessName' => '',
            'website' => $store->getBaseUrl(),
            'store' => [
                'name' => $store->getName(),
                'base_url' => $store->getBaseUrl(),
            ]
        ];

        $userDetail['quote'] = [
            'grand_total' => (string)$quote->getGrandTotal(),
        ];

        return $userDetail;
    }

}

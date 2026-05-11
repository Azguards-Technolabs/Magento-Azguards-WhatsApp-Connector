<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\RecipientResolverInterface;
use Azguards\WhatsAppConnect\Helper\ApiHelper;
use Azguards\WhatsAppConnect\Model\Config\EventConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class RecipientResolver
 *
 * Implements recipient resolution logic based on order lifecycle events.
 */
class RecipientResolver implements RecipientResolverInterface
{
    /**
     * @var ApiHelper
     */
    private ApiHelper $apiHelper;

    /**
     * @param ApiHelper $apiHelper
     */
    public function __construct(ApiHelper $apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolveByEntity(object $entity, string $eventCode): array
    {
        $order = null;
        if ($entity instanceof OrderInterface) {
            $order = $entity;
        } elseif (method_exists($entity, 'getOrder')) {
            $order = $entity->getOrder();
        } elseif ($entity instanceof CartInterface) {
            return $this->resolveFromQuote($entity);
        }

        if (!$order) {
            return ['mobileNumber' => '', 'countryCode' => ''];
        }

        $address = null;
        // Priority: Shipping Address for Shipments, Billing Address for others.
        // Fallback to Billing Address for Shipments if Shipping Address is null (e.g., virtual products).
        if ($eventCode === EventConfig::ORDER_SHIPMENT) {
            $address = $order->getShippingAddress() ?: $order->getBillingAddress();
        } else {
            $address = $order->getBillingAddress();
        }

        if (!$address) {
            return ['mobileNumber' => '', 'countryCode' => ''];
        }

        return $this->formatRecipient($address);
    }

    /**
     * Resolve recipient from Quote for abandoned cart events.
     *
     * @param CartInterface $quote
     * @return array
     */
    private function resolveFromQuote(CartInterface $quote): array
    {
        $address = $quote->getShippingAddress();
        if (!$address || !$address->getTelephone()) {
            $address = $quote->getBillingAddress();
        }

        if (!$address) {
            return ['mobileNumber' => '', 'countryCode' => ''];
        }

        return $this->formatRecipient($address);
    }

    /**
     * Format recipient data from address, ensuring normalization.
     *
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|\Magento\Quote\Api\Data\AddressInterface $address
     * @return array
     */
    private function formatRecipient($address): array
    {
        $telephone = (string)$address->getTelephone();
        $countryId = (string)$address->getCountryId();

        $countryCode = $this->apiHelper->getCountryCallingCodes($countryId ?: 'IN');
        $countryCode = preg_replace('/\D/', '', (string)$countryCode);
        $telephone = preg_replace('/\D/', '', $telephone);

        return [
            'mobileNumber' => $telephone,
            'countryCode' => $countryCode
        ];
    }
}

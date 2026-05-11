<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\RecipientResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Quote\Api\Data\CartInterface;

class RecipientResolver implements RecipientResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolveByEntity(object $entity): ?string
    {
        $telephone = null;

        if ($entity instanceof OrderInterface) {
            $billingAddress = $entity->getBillingAddress();
            $telephone = $billingAddress ? $billingAddress->getTelephone() : null;
        } elseif ($entity instanceof InvoiceInterface) {
            $order = $entity->getOrder();
            $billingAddress = $order ? $order->getBillingAddress() : null;
            $telephone = $billingAddress ? $billingAddress->getTelephone() : null;
        } elseif ($entity instanceof ShipmentInterface) {
            $order = $entity->getOrder();
            $shippingAddress = $order ? $order->getShippingAddress() : null;
            $telephone = $shippingAddress ? $shippingAddress->getTelephone() : null;
        } elseif ($entity instanceof CreditmemoInterface) {
            $order = $entity->getOrder();
            $billingAddress = $order ? $order->getBillingAddress() : null;
            $telephone = $billingAddress ? $billingAddress->getTelephone() : null;
        } elseif ($entity instanceof CartInterface) {
            $billingAddress = $entity->getBillingAddress();
            $telephone = $billingAddress ? $billingAddress->getTelephone() : null;
        }

        return $telephone;
    }
}

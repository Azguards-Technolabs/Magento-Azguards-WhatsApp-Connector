<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Service;

use Azguards\WhatsAppConnect\Api\RecipientResolverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;

class RecipientResolver implements RecipientResolverInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private \Magento\Sales\Api\OrderRepositoryInterface $orderRepository;

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Resolve mobile number dynamically based on entity/document type.
     *
     * @param object $entity
     * @return string|null
     */
    public function resolveByEntity(object $entity): ?string
    {
        if ($entity instanceof OrderInterface) {
            $address = $entity->getBillingAddress();
            return $address ? $address->getTelephone() : null;
        }

        if ($entity instanceof InvoiceInterface) {
            $orderId = $entity->getOrderId();
            if ($orderId) {
                try {
                    $order = $this->orderRepository->get($orderId);
                    $address = $order->getBillingAddress();
                    return $address ? $address->getTelephone() : null;
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        if ($entity instanceof ShipmentInterface) {
            $orderId = $entity->getOrderId();
            if ($orderId) {
                try {
                    $order = $this->orderRepository->get($orderId);
                    $address = $order->getShippingAddress();
                    return $address ? $address->getTelephone() : null;
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        if ($entity instanceof CreditmemoInterface) {
            $orderId = $entity->getOrderId();
            if ($orderId) {
                try {
                    $order = $this->orderRepository->get($orderId);
                    $address = $order->getBillingAddress();
                    return $address ? $address->getTelephone() : null;
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        return null;
    }
}
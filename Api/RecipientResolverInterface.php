<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api;

/**
 * Interface RecipientResolverInterface
 *
 * Provides logic to determine the correct recipient phone number based on the event type.
 */
interface RecipientResolverInterface
{
    /**
     * Resolve the recipient phone number and country code for a given entity and event.
     *
     * @param object $entity The Magento entity (Order, Invoice, Shipment, etc.)
     * @param string $eventCode The event code from EventConfig
     * @return array ['mobileNumber' => string, 'countryCode' => string]
     */
    public function resolveByEntity(object $entity, string $eventCode): array;
}

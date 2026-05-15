<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config;

class EventConfig
{
    public const MODULE_ENABLED = 'whatsApp_conector/general/enable';

    public const ORDER_CREATION = 'order_creation';
    public const ORDER_INVOICE = 'order_invoice';
    public const ORDER_SHIPMENT = 'order_shipment';
    public const ORDER_CANCELLATION = 'order_cancellation';
    public const ORDER_CREDIT_MEMO = 'order_credit_memo';
    public const ABANDON_CART = 'abandon_cart';

    private const EVENT_CONFIG = [
        self::ORDER_CREATION => [
            'request_type' => 'order_creation',
            'sync_contact' => true,
            'builder_group' => 'order_template',
            'enable_field' => 'enable_order',
        ],
        self::ORDER_INVOICE => [
            'request_type' => 'order_invoice',
            'sync_contact' => true,
            'builder_group' => 'order_invoice_template',
            'enable_field' => 'enable_invoice',
        ],
        self::ORDER_SHIPMENT => [
            'request_type' => 'order_shipment',
            'sync_contact' => true,
            'builder_group' => 'order_shipment_template',
            'enable_field' => 'enable_shipment',
        ],
        self::ORDER_CANCELLATION => [
            'request_type' => 'order_cancellation',
            'sync_contact' => true,
            'builder_group' => 'order_cancellation_template',
            'enable_field' => 'enable_cancellation',
        ],
        self::ORDER_CREDIT_MEMO => [
            'request_type' => 'order_credit_memo',
            'sync_contact' => true,
            'builder_group' => 'order_credit_memo_template',
            'enable_field' => 'enable_credit_memo',
        ],
        self::ABANDON_CART => [
            'request_type' => 'abandon_cart',
            'sync_contact' => true,
            'builder_group' => 'abandoned_cart_template',
            'enable_field' => 'enable_abandoned_cart',
        ],
    ];

    /**
     * Return configuration for the requested event.
     *
     * @param string $eventCode
     * @return array
     */
    public function get(string $eventCode): array
    {
        return self::EVENT_CONFIG[$eventCode] ?? [];
    }
}

<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Config;

class EventConfig
{
    public const MODULE_ENABLED = 'whatsApp_conector/general/enable';

    public const CUSTOMER_REGISTRATION = 'customer_registration';
    public const ORDER_CREATION = 'order_creation';
    public const ORDER_INVOICE = 'order_invoice';
    public const ORDER_SHIPMENT = 'order_shipment';
    public const ORDER_CANCELLATION = 'order_cancellation';
    public const ORDER_CREDIT_MEMO = 'order_credit_memo';

    private const EVENT_CONFIG = [
        self::CUSTOMER_REGISTRATION => [
            'template' => 'whatsApp_conector/user_registration/searchable_dropdown',
            'variables' => 'whatsApp_conector/user_registration/index',
            'media_handle' => 'whatsApp_conector/user_registration/media_handle',
            'request_type' => 'customer_registration',
            'sync_contact' => true,
        ],
        self::ORDER_CREATION => [
            'template' => 'whatsApp_conector/order_creation/searchable_dropdown_order_create',
            'variables' => 'whatsApp_conector/order_creation/order_create_variable',
            'media_handle' => 'whatsApp_conector/order_creation/media_handle',
            'request_type' => 'order_creation',
            'sync_contact' => false,
        ],
        self::ORDER_INVOICE => [
            'template' => 'whatsApp_conector/order_invoice/searchable_dropdown_order_invoice',
            'variables' => 'whatsApp_conector/order_invoice/order_invoice_variable',
            'media_handle' => 'whatsApp_conector/order_invoice/media_handle',
            'request_type' => 'order_invoice',
            'sync_contact' => false,
        ],
        self::ORDER_SHIPMENT => [
            'template' => 'whatsApp_conector/order_shipment/searchable_dropdown_order_shipment',
            'variables' => 'whatsApp_conector/order_shipment/order_shipment_variable',
            'media_handle' => 'whatsApp_conector/order_shipment/media_handle',
            'request_type' => 'order_shipment',
            'sync_contact' => false,
        ],
        self::ORDER_CANCELLATION => [
            'template' => 'whatsApp_conector/order_cancellation/searchable_dropdown_order_cancellation',
            'variables' => 'whatsApp_conector/order_cancellation/order_cancellation_variable',
            'media_handle' => 'whatsApp_conector/order_cancellation/media_handle',
            'request_type' => 'order_cancellation',
            'sync_contact' => false,
        ],
        self::ORDER_CREDIT_MEMO => [
            'template' => 'whatsApp_conector/order_credit_memo/searchable_dropdown_order_credit_memo',
            'variables' => 'whatsApp_conector/order_credit_memo/order_credit_memo_variable',
            'media_handle' => 'whatsApp_conector/order_credit_memo/media_handle',
            'request_type' => 'order_credit_memo',
            'sync_contact' => false,
        ],
    ];

    public function get(string $eventCode): array
    {
        return self::EVENT_CONFIG[$eventCode] ?? [];
    }
}

<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Model\Config\EventConfig;

class VariableOptionsProvider
{
    /**
     * Return dropdown options used to map template variables to Magento data sources.
     * Keys must match resolver source paths; values are admin-friendly labels.
     */
    public function getForEvent(string $eventCode): array
    {
        switch ($eventCode) {
            case EventConfig::CUSTOMER_REGISTRATION:
                return [
                    'firstname' => 'First Name',
                    'lastname' => 'Last Name',
                    'email' => 'Email',
                    'dob' => 'Date of Birth',
                    'gender' => 'Gender',
                    'created_at' => 'Created At',
                    'phone_number' => 'Phone Number',
                    'group_id' => 'Group ID',
                    'billing_address' => 'Billing Address',
                    'shipping_address' => 'Shipping Address',
                ];

            case EventConfig::ORDER_CREATION:
                return $this->orderBaseOptions();

            case EventConfig::ORDER_INVOICE:
                return $this->orderBaseOptions();

            case EventConfig::ORDER_SHIPMENT:
                return $this->orderBaseOptions();

            case EventConfig::ORDER_CANCELLATION:
                return $this->orderBaseOptions();

            case EventConfig::ORDER_CREDIT_MEMO:
                return $this->orderBaseOptions();

            case EventConfig::ABANDON_CART:
                return [
                    'entity_id' => 'Cart ID',
                    'created_at' => 'Cart Created At',
                    'updated_at' => 'Cart Updated At',
                    'grand_total' => 'Cart Grand Total',
                    'subtotal' => 'Cart Subtotal',
                    'items_count' => 'Cart Items Count',
                    'items_qty' => 'Cart Items Quantity',
                    'coupon_code' => 'Cart Coupon Code',
                    'customer_email' => 'Cart Customer Email',
                    'customer_firstname' => 'Cart Customer First Name',
                    'customer_lastname' => 'Cart Customer Last Name',
                    'customer_is_guest' => 'Is Guest Cart',
                    'is_active' => 'Cart Is Active',
                ];

            // Used by Campaign UI: resolve values from Customer + userDetail array.
            case 'campaign':
                return [
                    'firstname' => 'Customer First Name',
                    'lastname' => 'Customer Last Name',
                    'name' => 'Customer Full Name',
                    'email' => 'Customer Email',
                    'customer_id' => 'Customer ID',
                    // userDetail array keys
                    'mobileNumber' => 'Mobile Number',
                    'countryCode' => 'Country Code',
                    'businessName' => 'Business Name',
                    'website' => 'Website',
                ];
        }

        return [];
    }

    private function orderBaseOptions(): array
    {
        return [
            'entity_id' => 'Order ID',
            'increment_id' => 'Order Number',
            'status' => 'Order Status',
            'customer_email' => 'Customer Email',
            'customer_firstname' => 'Customer First Name',
            'customer_lastname' => 'Customer Last Name',
            'grand_total' => 'Grand Total',
            'subtotal' => 'Subtotal',
            'shipping_amount' => 'Shipping Amount',
            'payment_method' => 'Payment Method',
            'shipping_method' => 'Shipping Method',
            'created_at' => 'Order Date',
            'updated_at' => 'Last Updated',
            'billing_address' => 'Billing Address',
            'shipping_address' => 'Shipping Address',
        ];
    }
}

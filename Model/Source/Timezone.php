<?php
/**
 * Timezone source model for WhatsApp Campaign scheduling.
 * Provides IANA timezone options accepted by the WhatTalk scheduler API.
 */

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Timezone implements OptionSourceInterface
{
    /**
     * Return common IANA timezones as option array.
     *
     * The keys match values expected by the WhatTalk scheduler API.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $timezones = [
            'UTC'                  => 'UTC (00:00)',
            'Asia/Calcutta'        => 'India (IST, +05:30)',
            'Asia/Kolkata'         => 'India/Kolkata (IST, +05:30)',
            'Asia/Dubai'           => 'Dubai (GST, +04:00)',
            'Asia/Karachi'         => 'Pakistan (PKT, +05:00)',
            'Asia/Dhaka'           => 'Bangladesh (BST, +06:00)',
            'Asia/Singapore'       => 'Singapore (SGT, +08:00)',
            'Asia/Tokyo'           => 'Japan (JST, +09:00)',
            'Asia/Shanghai'        => 'China (CST, +08:00)',
            'Europe/London'        => 'London (GMT/BST)',
            'Europe/Paris'         => 'Paris (CET, +01:00)',
            'Europe/Berlin'        => 'Berlin (CET, +01:00)',
            'America/New_York'     => 'New York (EST/EDT)',
            'America/Chicago'      => 'Chicago (CST/CDT)',
            'America/Los_Angeles'  => 'Los Angeles (PST/PDT)',
            'America/Sao_Paulo'    => 'São Paulo (BRT, -03:00)',
            'Australia/Sydney'     => 'Sydney (AEST, +10:00)',
            'Pacific/Auckland'     => 'Auckland (NZST, +12:00)',
            'Africa/Cairo'         => 'Cairo (EET, +02:00)',
        ];

        $options = [];
        foreach ($timezones as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }

        return $options;
    }
}

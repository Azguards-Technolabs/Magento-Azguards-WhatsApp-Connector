<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class VariableResolver
{
    /**
     * Resolve a template string against an order object.
     *
     * @param string $template
     * @param OrderInterface $order
     * @return string
     */
    public function resolve(string $template, OrderInterface $order): string
    {
        return $this->resolveWithData($template, $this->buildOrderData($order));
    }

    /**
     * Resolve a template string against a plain array.
     *
     * @param string $template
     * @param array<string, mixed> $data
     * @return string
     */
    public function resolveWithData(string $template, array $data): string
    {
        if ($template === '') {
            return '';
        }

        $resolved = preg_replace_callback(
            '/\{\{\#items\}\}(.*?)\{\{\/items\}\}/s',
            function (array $matches) use ($data): string {
                $items = $data['items'] ?? [];
                if (!is_array($items) || $items === []) {
                    return '';
                }

                $rowTemplate = $matches[1] ?? '';
                $rows = [];
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $rows[] = $this->replaceVariables($rowTemplate, $item);
                }

                return implode('', $rows);
            },
            $template
        );

        return trim($this->replaceVariables((string)$resolved, $data));
    }

    /**
     * Return demo data used by preview and test message actions.
     *
     * @return array<string, mixed>
     */
    public function getSampleData(): array
    {
        return [
            'customer_firstname' => 'Zubair',
            'customer_lastname' => 'Shaikh',
            'customer_email' => 'zubair@example.com',
            'increment_id' => '10001',
            'grand_total' => '1200.00',
            'total_qty_ordered' => '3',
            'city' => 'Ahmedabad',
            'country_id' => 'IN',
            'items' => [
                ['name' => 'Canvas Frame', 'qty' => '1', 'price' => '450.00'],
                ['name' => 'Wooden Print', 'qty' => '2', 'price' => '375.00'],
            ],
        ];
    }

    /**
     * Build a normalized data array from an order entity.
     *
     * @param OrderInterface $order
     * @return array<string, mixed>
     */
    private function buildOrderData(OrderInterface $order): array
    {
        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();
        $address = $shippingAddress ?: $billingAddress;

        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            if (!$item instanceof OrderItemInterface) {
                continue;
            }

            $items[] = [
                'name' => (string)$item->getName(),
                'qty' => $this->normalizeNumber($item->getQtyOrdered()),
                'price' => $this->normalizePrice((float)$item->getPrice()),
            ];
        }

        return [
            'customer_firstname' => (string)$order->getCustomerFirstname(),
            'customer_lastname' => (string)$order->getCustomerLastname(),
            'customer_email' => (string)$order->getCustomerEmail(),
            'increment_id' => (string)$order->getIncrementId(),
            'grand_total' => $this->normalizePrice((float)$order->getGrandTotal()),
            'total_qty_ordered' => $this->normalizeNumber($order->getTotalQtyOrdered()),
            'city' => $address ? (string)$address->getCity() : '',
            'country_id' => $address ? (string)$address->getCountryId() : '',
            'items' => $items,
        ];
    }

    /**
     * Replace scalar variables in the given template.
     *
     * @param string $template
     * @param array<string, mixed> $data
     * @return string
     */
    private function replaceVariables(string $template, array $data): string
    {
        return (string)preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($data): string {
                $key = $matches[1] ?? '';
                if ($key === '') {
                    return '';
                }

                $value = $this->extractValue($data, $key);
                if (is_scalar($value) || $value === null) {
                    return (string)$value;
                }

                return '';
            },
            $template
        );
    }

    /**
     * Extract a nested value using dot notation.
     *
     * @param array<string, mixed> $data
     * @param string $path
     * @return mixed
     */
    private function extractValue(array $data, string $path)
    {
        if (array_key_exists($path, $data)) {
            return $data[$path];
        }

        $segments = explode('.', $path);
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return '';
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Normalize a numeric value for message output.
     *
     * @param float|int|string|null $value
     * @return string
     */
    private function normalizeNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float)$value;
        if ((int)$number === $number) {
            return (string)(int)$number;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    /**
     * Normalize a price value for message output.
     *
     * @param float $value
     * @return string
     */
    private function normalizePrice(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}

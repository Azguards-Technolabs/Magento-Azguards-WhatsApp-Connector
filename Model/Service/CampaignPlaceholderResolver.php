<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;

class CampaignPlaceholderResolver
{
    private TemplateVariableResolver $templateVariableResolver;
    private VariableOptionsProvider $variableOptionsProvider;
    private TemplateVariableRowsBuilder $variableRowsBuilder;

    public function __construct(
        TemplateVariableResolver $templateVariableResolver,
        VariableOptionsProvider $variableOptionsProvider,
        TemplateVariableRowsBuilder $variableRowsBuilder
    ) {
        $this->templateVariableResolver = $templateVariableResolver;
        $this->variableOptionsProvider = $variableOptionsProvider;
        $this->variableRowsBuilder = $variableRowsBuilder;
    }

    public function build(CustomerInterface $customer, array $userDetail, DataObject $template, array $variableOverrides = []): array
    {
        $variables = $this->extractVariables($template);
        $dataMap = $this->buildDataMap($customer, $userDetail);
        $allowedPaths = array_keys($this->variableOptionsProvider->getForEvent('campaign'));
        $positionToName = $this->buildPositionToNameMap($template);
        $placeholders = [];

        foreach ($variables as $variable) {
            $normalized = strtolower(trim($variable));
            $overrideKey = $this->pickOverrideKey($variable, $variableOverrides, $positionToName);
            if ($overrideKey !== null) {
                $override = $variableOverrides[$overrideKey];
                if (is_array($override)) {
                    $override = $override['value'] ?? $override['path'] ?? $override['literal'] ?? '';
                }
                $override = is_scalar($override) ? (string)$override : '';

                // Senior mapping: If override equals a known source path key, resolve dynamically.
                if ($override !== '' && in_array($override, $allowedPaths, true)) {
                    $placeholders[$variable] = (string)$this->templateVariableResolver->resolveValue(
                        $override,
                        [$customer, $userDetail]
                    );
                } else {
                    // Treat as literal override.
                    $placeholders[$variable] = $override;
                }
            } else {
                // Backwards compatible auto-resolution by variable name.
                // If template uses numeric placeholders, try to map 1/2/... -> api variable name for auto fill.
                $autoKey = $normalized;
                if (is_numeric($variable) && isset($positionToName[$variable])) {
                    $autoKey = strtolower(trim((string)$positionToName[$variable]));
                }
                $placeholders[$variable] = $dataMap[$autoKey] ?? '';
            }
        }

        return $placeholders;
    }

    private function pickOverrideKey(string $variable, array $variableOverrides, array $positionToName): ?string
    {
        $candidates = [$variable];
        if (is_numeric($variable) && isset($positionToName[$variable])) {
            $candidates[] = $positionToName[$variable];
        }

        foreach ($candidates as $key) {
            if (!array_key_exists($key, $variableOverrides)) {
                continue;
            }
            $val = $variableOverrides[$key];
            if ($val === null) {
                continue;
            }
            if (is_string($val) && trim($val) === '') {
                continue;
            }
            if (is_array($val) && ($val === [] || (($val['value'] ?? $val['path'] ?? $val['literal'] ?? '') === ''))) {
                continue;
            }
            return $key;
        }

        return null;
    }

    private function buildPositionToNameMap(DataObject $template): array
    {
        $externalTemplateId = (string)$template->getData('template_id');
        if ($externalTemplateId === '') {
            return [];
        }

        $rows = $this->variableRowsBuilder->buildByExternalTemplateId($externalTemplateId);
        if (empty($rows)) {
            return [];
        }

        $map = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pos = (string)($row['order'] ?? '');
            $name = (string)($row['type'] ?? $row['title'] ?? '');
            if ($pos === '' || $name === '') {
                continue;
            }
            $map[$pos] = $name;
        }

        return $map;
    }

    private function extractVariables(DataObject $template): array
    {
        $content = implode(' ', array_filter([
            (string)$template->getData('header'),
            (string)$template->getData('body'),
            (string)$template->getData('footer'),
        ]));

        preg_match_all('/\{\{\s*([^}]+?)\s*\}\}/', $content, $matches);
        $variables = [];
        foreach ($matches[1] ?? [] as $variable) {
            $variable = trim((string)$variable);
            if ($variable !== '' && !in_array($variable, $variables, true)) {
                $variables[] = $variable;
            }
        }

        return $variables;
    }

    private function buildDataMap(CustomerInterface $customer, array $userDetail): array
    {
        $firstName = (string)$customer->getFirstname();
        $lastName = (string)$customer->getLastname();
        $fullName = trim($firstName . ' ' . $lastName);

        return [
            'firstname' => $firstName,
            'first_name' => $firstName,
            'lastname' => $lastName,
            'last_name' => $lastName,
            'name' => $fullName,
            'full_name' => $fullName,
            'email' => (string)$customer->getEmail(),
            'customer_id' => (string)$customer->getId(),
            'mobile' => (string)($userDetail['mobileNumber'] ?? ''),
            'mobile_number' => (string)($userDetail['mobileNumber'] ?? ''),
            'phone' => (string)($userDetail['mobileNumber'] ?? ''),
            'country_code' => (string)($userDetail['countryCode'] ?? ''),
            'website' => (string)($userDetail['website'] ?? ''),
            'business_name' => (string)($userDetail['businessName'] ?? ''),
        ];
    }
}

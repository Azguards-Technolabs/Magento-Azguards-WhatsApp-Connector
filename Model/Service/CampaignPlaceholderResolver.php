<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\DataObject;

class CampaignPlaceholderResolver
{
    /**
     * @var TemplateVariableResolver
     */
    private TemplateVariableResolver $templateVariableResolver;

    /**
     * @var VariableOptionsProvider
     */
    private VariableOptionsProvider $variableOptionsProvider;

    /**
     * @var TemplateVariableRowsBuilder
     */
    private TemplateVariableRowsBuilder $variableRowsBuilder;

    /**
     * @param TemplateVariableResolver $templateVariableResolver
     * @param VariableOptionsProvider $variableOptionsProvider
     * @param TemplateVariableRowsBuilder $variableRowsBuilder
     */
    public function __construct(
        TemplateVariableResolver $templateVariableResolver,
        VariableOptionsProvider $variableOptionsProvider,
        TemplateVariableRowsBuilder $variableRowsBuilder
    ) {
        $this->templateVariableResolver = $templateVariableResolver;
        $this->variableOptionsProvider = $variableOptionsProvider;
        $this->variableRowsBuilder = $variableRowsBuilder;
    }

    /**
     * Build resolved placeholder values for a campaign message.
     *
     * @param DataObject $customer
     * @param array $userDetail
     * @param DataObject $template
     * @param array $variableOverrides
     * @return array
     */
    public function build(
        DataObject $customer,
        array $userDetail,
        DataObject $template,
        array $variableOverrides = []
    ): array {
        $variables = $this->extractVariables($template);
        $dataMap = $this->buildDataMap($customer, $userDetail);
        $allowedPaths = array_keys($this->variableOptionsProvider->getForEvent('campaign'));
        $positionToName = $this->buildPositionToNameMap($template);
        $placeholders = [];

        foreach ($variables as $variable) {
            $normalized = strtolower(trim($variable));
            $overrideKey = $this->pickOverrideKey($variable, $variableOverrides, $positionToName);

            // Resolve the descriptive name for the placeholder key if it's numeric (e.g., 1 -> name)
            $placeholderKey = (is_numeric($variable) && isset($positionToName[$variable]))
                ? (string)$positionToName[$variable]
                : $variable;

            if ($overrideKey !== null) {
                $override = $variableOverrides[$overrideKey];
                if (is_array($override)) {
                    $override = $override['value'] ?? $override['path'] ?? $override['literal'] ?? '';
                }
                $override = is_scalar($override) ? (string)$override : '';

                // Senior mapping: If override equals a known source path key, resolve dynamically.
                if ($override !== '' && in_array($override, $allowedPaths, true)) {
                    $placeholders[$placeholderKey] = (string)$this->templateVariableResolver->resolveValue(
                        $override,
                        [$customer, $userDetail]
                    );
                } else {
                    // Treat as literal override.
                    $placeholders[$placeholderKey] = $override;
                }
            } else {
                // Backwards compatible auto-resolution by variable name.
                $autoKey = $normalized;
                if (is_numeric($variable) && isset($positionToName[$variable])) {
                    $autoKey = strtolower(trim((string)$positionToName[$variable]));
                }
                $placeholders[$placeholderKey] = $dataMap[$autoKey] ?? '';
            }
        }

        return $placeholders;
    }

    /**
     * Pick the most suitable override key for a variable.
     *
     * @param string $variable
     * @param array $variableOverrides
     * @param array $positionToName
     * @return string|null
     */
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

    /**
     * Build a map from numeric positions to descriptive variable names.
     *
     * @param DataObject $template
     * @return array
     */
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

    /**
     * Extract raw placeholder variables from template content.
     *
     * @param DataObject $template
     * @return array
     */
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

    /**
     * Build a normalized data map for placeholder auto-resolution.
     *
     * @param DataObject $customer
     * @param array $userDetail
     * @return array
     */
    private function buildDataMap(DataObject $customer, array $userDetail): array
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

<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

class TemplateVariableResolver
{
    /**
     * Resolve template variable mappings against available contexts.
     *
     * @param array $mappings
     * @param array $contexts
     * @return array
     */
    public function resolve(array $mappings, array $contexts): array
    {
        $resolved = [];

        foreach ($mappings as $mapping) {
            // Prefer stable variable identifier (e.g. `order_id`, `name`) when present.
            // Some config UIs store numeric sort order in `order` and the actual variable name in `identifier`.
            $parameterName = $this->normalizeParameterName($mapping['identifier'] ?? '');
            if ($parameterName === '') {
                $parameterName = isset($mapping['order']) ? (string)$mapping['order'] : '';
            }
            $sourcePath = isset($mapping['limit']) ? trim((string)$mapping['limit']) : '';

            if ($parameterName === '' || $sourcePath === '') {
                continue;
            }

            // Backward-compatible business rule:
            // In WhatsApp templates, `order_id` is expected to be the human-readable order number (increment_id),
            // not the internal entity_id. Many existing configs map `order_id` -> `entity_id`.
            if ($parameterName === 'order_id' && $sourcePath === 'entity_id') {
                $sourcePath = 'increment_id';
            }

            $resolved[$parameterName] = $this->resolveValue($sourcePath, $contexts);
        }

        return $resolved;
    }

    /**
     * Normalize a raw parameter name from UI mapping data.
     *
     * @param mixed $raw
     * @return string
     */
    private function normalizeParameterName($raw): string
    {
        if ($raw === null) {
            return '';
        }

        $value = is_scalar($raw) ? (string)$raw : '';
        $value = trim($value);

        // Many config UIs store JSON-encoded strings (e.g. "\"order_id\"").
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $value = $decoded;
            }
        }

        // Final cleanup: strip any remaining wrapping quotes.
        $value = trim($value, "\"' \t\n\r\0\x0B");

        return $value;
    }

    /**
     * Resolve a single source path from the available contexts.
     *
     * @param string $sourcePath
     * @param array $contexts
     * @return mixed
     */
    public function resolveValue(string $sourcePath, array $contexts)
    {
        if ($sourcePath === '') {
            return '';
        }

        foreach ($contexts as $context) {
            $value = $this->extractFromContext($context, $sourcePath);
            if ($value !== null && $value !== '') {
                return $this->normalizeValue($value);
            }
        }

        return '';
    }

    /**
     * Extract a source path value from a context object or array.
     *
     * @param mixed $context
     * @param string $sourcePath
     * @return mixed
     */
    private function extractFromContext($context, string $sourcePath)
    {
        if (!is_object($context) && !is_array($context)) {
            return null;
        }

        if (preg_match('/^([^\[]+)\[(\d+)\]\.(.+)$/', $sourcePath, $matches)) {
            $collection = $this->extractFromContext($context, $matches[1]);
            $index = (int)$matches[2];
            $remainder = $matches[3];

            if (is_array($collection) && isset($collection[$index])) {
                return $this->extractFromContext($collection[$index], $remainder);
            }

            return null;
        }

        if (strpos($sourcePath, '.') !== false) {
            $segments = explode('.', $sourcePath);
            $value = $context;

            foreach ($segments as $segment) {
                $value = $this->extractSegment($value, $segment);
                if ($value === null) {
                    return null;
                }
            }

            return $value;
        }

        return $this->extractSegment($context, $sourcePath);
    }

    /**
     * Extract a single segment from a context object or array.
     *
     * @param mixed $context
     * @param string $segment
     * @return mixed
     */
    private function extractSegment($context, string $segment)
    {
        if (is_array($context)) {
            return $context[$segment] ?? null;
        }

        if (!is_object($context)) {
            return null;
        }

        $camelized = str_replace(' ', '', ucwords(str_replace('_', ' ', $segment)));
        $methods = [
            'get' . $camelized,
            'is' . $camelized,
            $segment,
        ];

        foreach ($methods as $method) {
            if (method_exists($context, $method)) {
                return $context->{$method}();
            }
        }

        if (method_exists($context, 'getData')) {
            $value = $context->getData($segment);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Normalize a resolved value for safe output.
     *
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue($value)
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string)$value;
        }

        return json_encode($value);
    }
}

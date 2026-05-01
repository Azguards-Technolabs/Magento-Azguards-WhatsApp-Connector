<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

/**
 * Service for robustly extracting and resolving media handlers from various data formats.
 * Designed with senior-level architecture to handle complex, nested JSON data from sync processes.
 */
class MediaResolver
{
    /**
     * Extracts the most appropriate document ID/handler from a raw value.
     *
     * Prioritizes internal document_id over Meta's header_handle.
     *
     * @param mixed $value Raw data from database or API
     * @return string|null Resolved handler ID
     */
    public function resolveHandler($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If it's already a clean string and not JSON, return it
        if (is_string($value) && !$this->isJson($value)) {
            // Senior heuristic: Skip if it looks like a sentence (too many spaces) or is empty
            if (substr_count($value, ' ') > 3 || trim($value) === '') {
                return null;
            }
            return $value;
        }

        // Decode if it's a JSON string
        $data = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($data)) {
            return is_scalar($data) ? (string)$data : null;
        }

        // Priority 1: Nested media document_id (Specific to recent sync format)
        if (isset($data['media']['document_id'])) {
            return (string)$data['media']['document_id'];
        }

        // Priority 2: Direct document_id or docId
        $directId = $data['document_id'] ?? $data['docId'] ?? $data['id'] ?? null;
        if ($directId) {
            return (string)$directId;
        }

        // Priority 3: Meta header_handle (Array or String)
        if (isset($data['header_handle'])) {
            $handle = $data['header_handle'];
            if (is_array($handle)) {
                return (string)($handle[0] ?? '');
            }
            return (string)$handle;
        }

        // Fallback: Check if it's a flat array where the ID is the first element
        if (isset($data[0]) && is_string($data[0])) {
            return $data[0];
        }

        return null;
    }

    /**
     * Checks if a string is a JSON object/array.
     *
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        $string = trim($string);
        return str_starts_with($string, '{') || str_starts_with($string, '[');
    }
}

<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Magento\Framework\DataObject;

class TemplateVariableExtractor
{
    /**
     * Extract unique variables in first-seen order from a template (header/body/footer + carousel cards).
     *
     * Returns raw variable names as they appear inside {{ ... }} (trimmed).
     *
     * @param DataObject $template
     * @return array
     */
    public function extractFromTemplate(DataObject $template): array
    {
        $parts = [
            (string)$template->getData('header'),
            (string)$template->getData('body'),
            (string)$template->getData('footer'),
        ];

        $cards = $template->getData('carousel_cards');
        if (is_string($cards)) {
            $decoded = json_decode($cards, true);
            if (is_array($decoded)) {
                $cards = $decoded;
            }
        }

        if (is_array($cards)) {
            foreach ($cards as $card) {
                if (!is_array($card)) {
                    continue;
                }
                $parts[] = (string)($card['header'] ?? '');
                $parts[] = (string)($card['body'] ?? '');
                $parts[] = (string)($card['footer'] ?? '');
            }
        }

        return $this->extractFromText(implode(' ', array_filter($parts)));
    }

    /**
     * Extract unique variables in first-seen order from text.
     *
     * @param string $text
     * @return array
     */
    public function extractFromText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('/\{\{\s*([^}]+?)\s*\}\}/', $text, $matches);
        $raw = $matches[1] ?? [];
        if (!is_array($raw) || $raw === []) {
            return [];
        }

        $variables = [];
        foreach ($raw as $value) {
            $name = trim((string)$value);
            if ($name === '' || in_array($name, $variables, true)) {
                continue;
            }
            $variables[] = $name;
        }

        return $variables;
    }

    /**
     * Build config-style rows (title/identifier/order/type) from variables.
     *
     * @param array $variables Raw extracted variables (strings)
     * @param array $examples Optional examples array for numeric variables (0-indexed)
     */
    public function buildRows(array $variables, array $examples = []): array
    {
        $rows = [];
        $order = 1;
        foreach ($variables as $variable) {
            $type = trim((string)$variable);
            if ($type === '') {
                continue;
            }

            $title = $type;
            if (is_numeric($type)) {
                $idx = (int)$type - 1;
                if (isset($examples[$idx]) && is_scalar($examples[$idx]) && (string)$examples[$idx] !== '') {
                    $title = (string)$examples[$idx];
                }
            }

            $cleanType = trim($type, '{} ');
            $rows[] = [
                'title' => $title,
                'identifier' => 'catalogsearch_fulltext_' . $cleanType,
                'order' => $order++,
                'type' => $cleanType,
            ];
        }

        return $rows;
    }
}

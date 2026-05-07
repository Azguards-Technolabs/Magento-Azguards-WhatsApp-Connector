<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Psr\Log\LoggerInterface;

class MetaTemplatePayloadBuilder
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Build the valid, minimal, and clean WhatsApp template creation payload 
     * based on the strict custom structure
     *
     * @param TemplateInterface $template
     * @return array
     */
    public function build(TemplateInterface $template): array
    {
        $templateNameStr = trim((string)$template->getTemplateName());
        $templateName = preg_replace('/_+/', '_', strtolower(str_replace([' ', '-'], '_', $templateNameStr)));
        
        $type = strtoupper((string)($template->getTemplateType() ?: 'TEXT'));
        if ($type === 'MEDIA') {
            $format = strtoupper((string)$template->getHeaderFormat() ?: '');
            if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
                $type = $format;
            }
        }
        
        $payload = [
            'name'     => $templateName,
            'category' => strtoupper((string)($template->getTemplateCategory() ?: 'UTILITY')),
            'type'     => $type,
            'language' => $template->getLanguage() ?: 'en_US',
        ];

        // 7. HEADER
        $header = $this->buildHeader($template);
        if (!empty($header)) {
            $payload['header'] = $header;
        }

        // BODY
        $body = $this->buildBody($template);
        if (!empty($body)) {
            $payload['body'] = $body;
        }

        // FOOTER
        $footer = $this->buildFooter($template);
        if (!empty($footer)) {
            $payload['footer'] = $footer;
        }

        // BUTTONS
        $buttons = $this->buildButtons($template->getButtons());
        if (!empty($buttons)) {
            $payload['buttons'] = $buttons;
        }

        // Ensure no empty arrays at root level if not required, 
        // though array_filter is risky if language or type could be false
        return $payload;
    }

    /**
     * Build the header payload section.
     *
     * @param TemplateInterface $template
     * @return array|null
     */
    protected function buildHeader(TemplateInterface $template): ?array
    {
        $format = strtoupper((string)$template->getHeaderFormat() ?: 'TEXT');

        if ($format === 'TEXT' && $template->getHeader()) {
            $headerText = $this->cleanText($template->getHeader());
            if (empty($headerText)) {
                return null;
            }

            return [
                'type'   => 'HEADER',
                'format' => 'TEXT',
                'text'   => $headerText
            ];
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $documentId = $this->cleanText((string)$template->getHeaderHandle());
            
            $header = [
                'type'   => 'HEADER',
                'format' => $format
            ];
            
            if ($documentId) {
                $header['media'] = [
                    'id' => $documentId
                ];
            }
            
            return $header;
        }

        return null;
    }

    /**
     * Build the body payload section.
     *
     * @param TemplateInterface $template
     * @return array|null
     */
    protected function buildBody(TemplateInterface $template): ?array
    {
        $text = $template->getBody();
        if (empty($text)) {
            return null;
        }

        // Requirement: Convert items loop into single variable {{items_summary}}
        $text = preg_replace('/\{\{\#items\}\}[\s\S]*?\{\{\/items\}\}/', '{{items_summary}}', $text);

        $result = $this->processTextVariables($text);
        
        if (empty($result['text'])) {
            return null;
        }

        $body = [
            'type'   => 'BODY',
            'format' => 'TEXT',
            'text'   => $result['text']
        ];

        if (!empty($result['params'])) {
            $body['param'] = $result['params'];
        }

        return $body;
    }

    /**
     * Build the footer payload section.
     *
     * @param TemplateInterface $template
     * @return array|null
     */
    protected function buildFooter(TemplateInterface $template): ?array
    {
        $text = $this->cleanText((string)$template->getFooter());
        if ($text) {
            return [
                'type' => 'FOOTER',
                'text' => $text
            ];
        }
        return null;
    }

    /**
     * Build the buttons payload section.
     *
     * @param string|null $buttonsJson
     * @return array
     */
    protected function buildButtons(?string $buttonsJson): array
    {
        if (!$buttonsJson) {
            return [];
        }

        $buttonsData = json_decode($buttonsJson, true);
        if (!is_array($buttonsData)) {
            return [];
        }

        if (isset($buttonsData['type'])) {
            $buttonsData = [$buttonsData];
        }

        $buttons = [];
        foreach ($buttonsData as $btn) {
            if (!is_array($btn) || empty($btn['type'])) {
                continue;
            }

            $type = strtoupper((string)$btn['type']);
            $text = $this->cleanText((string)($btn['text'] ?? ''));
            
            if (empty($text) && !in_array($type, ['CATALOG'])) {
                continue; // "Remove empty buttons"
            }

            $button = [
                'type' => $type,
            ];
            
            if (!empty($text)) {
                $button['text'] = $text;
            }

            switch ($type) {
                case 'URL':
                    $urlValue = $this->cleanText((string)($btn['button_url'] ?? $btn['url'] ?? $btn['value'] ?? ''));
                    if (empty($urlValue)) {
                        continue 2; // "Remove empty buttons" / "Do NOT send empty URL"
                    }
                    $urlResult = $this->processTextVariables($urlValue, true);
                    $button['url'] = $urlResult['text'];
                    if (!empty($urlResult['params'])) {
                        $button['param'] = $urlResult['params'];
                    }
                    break;

                case 'PHONE_NUMBER':
                case 'PHONE':
                    $button['type'] = 'PHONE_NUMBER';
                    $phone = $this->cleanText((string)($btn['phone_number'] ?? $btn['value'] ?? ''));
                    if (empty($phone)) {
                        continue 2;
                    }
                    $button['phone_number'] = $phone;
                    break;

                case 'QUICK_REPLY':
                    // Just type and text
                    break;
                    
                case 'CATALOG':
                    // Just type and text
                    break;
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    /**
     * Process text to extract variables in order of appearance
     * Ensure names remain in text (not numeric), and build the param array.
     *
     * @param string $text
     * @param bool $isButtonUrl
     * @return array
     */
    protected function processTextVariables(string $text, bool $isButtonUrl = false): array
    {
        // First clean the text
        $text = $this->cleanText($text);
        
        $params = [];
        $variableMap = [];
        $counter = 0;

        $transformedText = preg_replace_callback(
            '/\{\{\s*(?:var\s+)?(.*?)\s*\}\}/', // matches {{var name}} or {{name}}
            function ($matches) use (&$params, &$variableMap, &$counter, $isButtonUrl) {
                $originalVar = trim($matches[1]);

                // Extract clean property name
                $prop = $originalVar;
                if (str_contains($prop, '.')) {
                    $parts = explode('.', $prop);
                    $prop = end($parts);
                }
                $prop = str_replace('()', '', $prop);
                
                // Ensure name is clean for the payload (e.g. customer_firstname)
                $cleanVarName = preg_replace('/[^a-zA-Z0-9_]/', '', $prop);
                if (empty($cleanVarName)) {
                    $cleanVarName = 'var';
                }

                if ($isButtonUrl) {
                    $sampleVal = (string)$this->getSampleValue($prop);
                    
                    if (!isset($variableMap[$cleanVarName])) {
                        $variableMap[$cleanVarName] = $sampleVal;
                        // "param ma 00111" -> use sample value
                        $params[] = $sampleVal; 
                    }
                    
                    // "button logic pela jevu j baseUrl/{{order_id}}" -> keep variable name in text
                    return '{{' . $cleanVarName . '}}';
                }

                if (!isset($variableMap[$cleanVarName])) {
                    $counter++;
                    $variableMap[$cleanVarName] = $counter;
                    // User requested: "attribute_name": "customer_firstname" (no braces)
                    $params[] = $cleanVarName;
                }

                // Return format: {{1}}, {{2}} in the text body
                return '{{' . $variableMap[$cleanVarName] . '}}';
            },
            $text
        );
        
        // Final clean text in case regex introduced anything
        $transformedText = $this->cleanText($transformedText);

        return [
            'text'   => $transformedText,
            'params' => $params
        ];
    }

    /**
     * Get sample value for a variable.
     *
     * @param string $variable
     * @return string
     */
    private function getSampleValue(string $variable): string
    {
        $samples = [
            'customer_firstname' => 'Zubair',
            'customer_lastname'  => 'Sayed',
            'customer_email'     => 'zubair@example.com',
            'increment_id'       => '#10001',
            'items_summary'      => 'Shirt x2 = $150',
            'grand_total'        => '$150',
            'subtotal'           => '$140',
            'status'             => 'Processing',
            'created_at'         => '2023-10-27',
            'city'               => 'Dubai',
            // Adding a few generic fallbacks
            'name'               => 'Zubair',
            'order_id'           => '#10001',
            'amount'             => '$150'
        ];

        return $samples[$variable] ?? $variable;
    }

    /**
     * Clean text strings according to strict rules
     * - Trim all strings
     * - Remove extra spaces
     * - Remove trailing spaces/newlines
     * - No double spaces in text
     *
     * @param string $text
     * @return string
     */
    private function cleanText(string $text): string
    {
        // Ensure no empty string is returned if it's supposed to be null
        if (trim($text) === '') {
            return '';
        }
        
        // Remove double spaces while keeping legitimate newlines
        // We replace any horizontal whitespace >= 2 with a single space
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        
        // Trim each line individually and remove multiple adjacent newlines
        $lines = explode("\n", $text);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '') {
                $cleanLines[] = $trimmed;
            }
        }
        
        return implode("\n", $cleanLines);
    }
}

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
     * Build the payload for the Meta API
     *
     * @param TemplateInterface $template
     * @return array
     */
    public function build(TemplateInterface $template): array
    {
        $payload = [
            'name'     => strtolower(str_replace([' ', '-'], '_', $template->getTemplateName())),
            'language' => $template->getLanguage() ?: 'en_US',
            'category' => strtoupper((string)($template->getTemplateCategory() ?: 'UTILITY')),
            'components' => []
        ];

        $components = [];

        if ($template->getTemplateType() === 'CAROUSEL') {
            // Restore Carousel logic
            $payload['carouselFormat'] = $this->resolveCarouselFormat($template);
            $payload['carousel']       = $this->buildCarouselCards($template);
            $body = $this->buildBody($template);
            if ($body) {
                $components[] = $body;
            }
        } else {
            $header = $this->buildHeader($template);
            if ($header) {
                $components[] = $header;
            }

            $body = $this->buildBody($template);
            if ($body) {
                $components[] = $body;
            }

            $footer = $this->buildFooter($template);
            if ($footer) {
                $components[] = $footer;
            }

            $buttons = $this->buildButtons($template->getButtons());
            if (!empty($buttons)) {
                $components[] = [
                    'type' => 'BUTTONS',
                    'buttons' => $buttons
                ];
            }
        }

        $payload['components'] = $components;

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
        $format = $template->getHeaderFormat() ?: 'TEXT';

        if ($format === 'TEXT' && $template->getHeader()) {
            $headerText = $template->getHeader();
            $result     = $this->processTextVariables($headerText);

            return [
                'type'   => 'HEADER',
                'format' => 'TEXT',
                'text'   => $result['text']
            ];
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $imageUrl = $template->getHeaderImage();
            if ($imageUrl) {
                return [
                    'type'   => 'HEADER',
                    'format' => $format,
                    'example' => [
                        'header_handle' => [$imageUrl]
                    ]
                ];
            }
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
        if ($text) {
            // Requirement 3: Convert items loop into single variable {{items_summary}}
            $text = preg_replace('/\{\{\#items\}\}[\s\S]*?\{\{\/items\}\}/', '{{items_summary}}', $text);

            $result = $this->processTextVariables($text);

            $body = [
                'type'   => 'BODY',
                'text'   => $result['text']
            ];

            if (!empty($result['params'])) {
                $body['example'] = [
                    'body_text' => [$result['params']]
                ];
            }

            return $body;
        }

        return null;
    }

    /**
     * Build the footer payload section.
     *
     * @param TemplateInterface $template
     * @return array|null
     */
    protected function buildFooter(TemplateInterface $template): ?array
    {
        $text = $template->getFooter();
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

            $type   = strtoupper((string)$btn['type']);
            $button = ['type' => $type];

            switch ($type) {
                case 'URL':
                    $button['text'] = $btn['text'] ?? '';
                    $urlValue = (string)($btn['button_url'] ?? $btn['url'] ?? $btn['value'] ?? '');
                    $urlResult = $this->processTextVariables($urlValue);
                    $button['url'] = $urlResult['text'];
                    if (!empty($urlResult['params'])) {
                        $button['example'] = $urlResult['params'];
                    }
                    break;

                case 'PHONE_NUMBER':
                    $button['text']  = $btn['text'] ?? '';
                    $button['phone_number'] = $btn['phone_number'] ?? ($btn['value'] ?? '');
                    break;

                case 'QUICK_REPLY':
                    $button['text'] = $btn['text'] ?? '';
                    break;
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    /**
     * Process text to transform named variables to numeric and extract params.
     *
     * Example: "Hello {{var order.customer_firstname}}" -> ["text" => "Hello {{1}}", "params" => ["Zubair"]]
     *
     * @param string $text
     * @return array
     */
    protected function processTextVariables(string $text): array
    {
        $params = [];
        $variableMap = []; // To track unique variables and their assigned indices
        $counter = 0;

        $transformedText = preg_replace_callback(
            '/\{\{\s*(?:var\s+)?(.*?)\s*\}\}/',
            function ($matches) use (&$params, &$variableMap, &$counter) {
                $originalVar = trim($matches[1]);

                // If we've already seen this variable, reuse its index
                if (isset($variableMap[$originalVar])) {
                    $index = $variableMap[$originalVar];
                } else {
                    $counter++;
                    $index = $counter;
                    $variableMap[$originalVar] = $index;

                    // Extract actual property name if it's order.property or items.property
                    $prop = $originalVar;
                    if (str_contains($prop, '.')) {
                        $parts = explode('.', $prop);
                        $prop = end($parts);
                        $prop = str_replace('()', '', $prop);
                    }

                    $sampleVal = $this->getSampleValue($prop);
                    $params[] = $sampleVal;
                }

                return '{{' . $index . '}}';
            },
            $text
        );

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
            'customer_lastname' => 'Sayed',
            'customer_email' => 'zubair@example.com',
            'increment_id' => '#10001',
            'items_summary' => 'Shirt x2 = $150',
            'grand_total' => '$150',
            'subtotal' => '$140',
            'status' => 'Processing',
            'created_at' => '2023-10-27',
            'city' => 'Dubai'
        ];

        return $samples[$variable] ?? $variable;
    }

    /**
     * Build carousel card payloads.
     */
    protected function buildCarouselCards(TemplateInterface $template): array
    {
        $cardsStr  = $template->getCarouselCards();
        $cardsData = $cardsStr ? json_decode($cardsStr, true) : [];
        $cards     = [];

        foreach ($cardsData as $cardData) {
            $card = [];
            $headerFormat = strtoupper((string)($cardData['header_format'] ?? ''));
            if (in_array($headerFormat, ['IMAGE', 'VIDEO'], true) && !empty($cardData['header_handle'])) {
                $card['header'] = [
                    'type'   => 'HEADER',
                    'format' => $headerFormat,
                    'media'  => [
                        'document_id' => $cardData['header_handle']
                    ]
                ];
            }
            if (!empty($cardData['body'])) {
                $bodyResult   = $this->processTextVariables((string)$cardData['body']);
                $card['body'] = [
                    'type'   => 'BODY',
                    'text'   => $bodyResult['text']
                ];
                if (!empty($bodyResult['params'])) {
                    $card['body']['example'] = [
                        'body_text' => [$bodyResult['params']]
                    ];
                }
            }
            $cardButtons = $cardData['buttons'] ?? ($cardData['buttons_json'] ?? null);
            if (!empty($cardButtons)) {
                $buttons = $this->buildButtons(is_string($cardButtons) ? $cardButtons : json_encode($cardButtons));
                if (!empty($buttons)) {
                    $card['buttons'] = $buttons;
                }
            }
            $cards[] = $card;
        }
        return $cards;
    }

    /**
     * Resolve the carousel media format.
     */
    protected function resolveCarouselFormat(TemplateInterface $template): string
    {
        $storedFormat = strtoupper((string)$template->getCarouselFormat());
        if (in_array($storedFormat, ['IMAGE', 'VIDEO'], true)) {
            return $storedFormat;
        }
        return 'IMAGE';
    }
}

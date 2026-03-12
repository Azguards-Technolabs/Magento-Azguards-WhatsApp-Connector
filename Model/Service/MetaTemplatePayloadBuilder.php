<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;
use Psr\Log\LoggerInterface;

class MetaTemplatePayloadBuilder
{
    private LoggerInterface $logger;

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
            'name' => $template->getTemplateName(),
            'language' => $template->getLanguage(),
            'category' => $template->getTemplateCategory(),
            'type' => $template->getTemplateType() ?: 'TEXT'
        ];

        if ($template->getTemplateType() === 'CAROUSEL') {
            $payload['components'] = [$this->buildCarousel($template)];
            return $payload;
        }

        // Header
        $header = $this->buildHeader($template);
        if ($header) {
            $payload['header'] = $header;
        }

        // Body
        $body = $this->buildBody($template);
        if ($body) {
            $payload['body'] = $body;
        }

        // Footer
        $footer = $this->buildFooter($template);
        if ($footer) {
            $payload['footer'] = $footer;
        }

        // Buttons
        $buttons = $this->buildButtons($template->getButtons());
        if (!empty($buttons)) {
            $payload['buttons'] = $buttons;
        }

        // Special Template Handling
        if ($template->getTemplateType() === 'LTO' && $template->getLimitedTimeOffer()) {
            $ltoData = json_decode($template->getLimitedTimeOffer(), true);
            if ($ltoData) {
                // Adjust LTO implementation according to Meta specs
                $payload['limited_time_offer'] = [
                    'type' => 'LIMITED_TIME_OFFER',
                    'text' => $ltoData['text'],
                    'expiration_minutes' => $ltoData['expiration_minutes']
                ];
            }
        }

        return $payload;
    }

    protected function buildHeader(TemplateInterface $template): ?array
    {
        $format = $template->getHeaderFormat() ?: 'TEXT';

        if ($format === 'TEXT' && $template->getHeader()) {
            $headerText = $template->getHeader();
            $result = $this->processTextVariables($headerText);
            
            $header = [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $result['text']
            ];

            if (!empty($result['params'])) {
                $header['param'] = $result['params'];
            }

            return $header;
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $documentId = $template->getHeaderHandle();
            if ($documentId) {
                $this->logger->info('Payload Builder: Building media header payload', [
                    'template_name' => $template->getTemplateName(),
                    'format' => $format,
                    'document_id' => $documentId
                ]);
                return [
                    'type' => 'HEADER',
                    'format' => $format,
                    'media' => [
                        'document_id' => $documentId
                    ]
                ];
            }
        }

        return null;
    }

    protected function buildBody(TemplateInterface $template): ?array
    {
        $text = $template->getBody();
        if ($text) {
            $result = $this->processTextVariables($text);
            
            $body = [
                'type' => 'BODY',
                'format' => 'TEXT',
                'text' => $result['text']
            ];

            // Try to extract body_examples_json directly if the data structure has it.
            // Since it is not in the TemplateInterface yet, fallback to fetching from GetData or generate
            // For now, assume it might be passed via $template->getData('body_examples_json')
            $examplesStr = $template->getData('body_examples_json');
            $examples = $examplesStr ? json_decode($examplesStr, true) : [];

            if (!empty($examples)) {
                $body['param'] = $examples;
            } elseif (!empty($result['params'])) {
                $body['param'] = $result['params'];
            }

            return $body;
        }

        return null;
    }

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

    protected function buildButtons(?string $buttonsJson): array
    {
        if (!$buttonsJson) {
            return [];
        }

        $buttonsData = json_decode($buttonsJson, true);
        if (!is_array($buttonsData)) {
            return [];
        }

        $buttons = [];
        foreach ($buttonsData as $btn) {
            $button = ['type' => $btn['type']];

            if ($btn['type'] === 'URL') {
                $button['text'] = $btn['text'];
                $button['value'] = $btn['url'] ?? ($btn['value'] ?? '');
            } elseif ($btn['type'] === 'PHONE_NUMBER') {
                $button['text'] = $btn['text'];
                $button['value'] = $btn['phone_number'] ?? ($btn['value'] ?? '');
            } elseif ($btn['type'] === 'QUICK_REPLY') {
                $button['text'] = $btn['text'];
            } elseif ($btn['type'] === 'OTP') {
                $button['otp_type'] = $btn['otp_type'];
                // For OTP, text might not be required or could be specific
            } elseif ($btn['type'] === 'COPY_CODE') {
                // Copy code button does not need text
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    protected function buildCarousel(TemplateInterface $template): array
    {
        $cardsStr = $template->getCarouselCards();
        $cardsData = $cardsStr ? json_decode($cardsStr, true) : [];
        $cards = [];

        foreach ($cardsData as $cardData) {
            $components = [];

            // Header
            $headerFormat = $cardData['header_format'] ?? 'TEXT';
            if ($headerFormat === 'TEXT' && !empty($cardData['header'])) {
                $components[] = [
                    'type' => 'HEADER',
                    'format' => 'TEXT',
                    'text' => $cardData['header']
                ];
            } elseif (in_array($headerFormat, ['IMAGE', 'VIDEO', 'DOCUMENT']) && !empty($cardData['header_handle'])) {
                $this->logger->info('Payload Builder: Building carousel media header payload', [
                    'format' => $headerFormat,
                    'document_id' => $cardData['header_handle']
                ]);
                $components[] = [
                    'type' => 'HEADER',
                    'format' => $headerFormat,
                    'media' => [
                        'document_id' => $cardData['header_handle']
                    ]
                ];
            }

            // Body
            if (!empty($cardData['body'])) {
                $components[] = [
                    'type' => 'BODY',
                    'text' => $cardData['body']
                ];
            }

            // Buttons
            if (!empty($cardData['buttons'])) {
                $buttons = $this->buildButtons(is_string($cardData['buttons']) ? $cardData['buttons'] : json_encode($cardData['buttons']));
                if (!empty($buttons)) {
                    $components[] = [
                        'type' => 'BUTTONS',
                        'buttons' => $buttons
                    ];
                }
            }

            $cards[] = [
                'components' => $components
            ];
        }

        return [
            'type' => 'CAROUSEL',
            'cards' => $cards
        ];
    }

    /**
     * Process text to transform named variables to numeric and extract params
     * 
     * Example: "Hello {{name}}" -> ["text" => "Hello {{1}}", "params" => ["name"]]
     */
    protected function processTextVariables(string $text): array
    {
        $params = [];
        $transformedText = preg_replace_callback(
            '/\{\{(.*?)\}\}/',
            function ($matches) use (&$params) {
                $paramValue = trim($matches[1]);
                // If the parameter is already numeric, use it but still track for params
                if (is_numeric($paramValue)) {
                    $index = (int)$paramValue;
                    $params[$index - 1] = "sample_" . $index;
                } else {
                    $params[] = $paramValue;
                }
                return '{{' . count($params) . '}}';
            },
            $text
        );

        // Normalize params array to ensure it's a list
        ksort($params);
        $params = array_values($params);

        return [
            'text' => $transformedText,
            'params' => $params
        ];
    }
}

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
            'category' => strtoupper((string)$template->getTemplateCategory()),
            'type' => $template->getTemplateType() ?: 'TEXT'
        ];

        if ($template->getTemplateType() === 'CAROUSEL') {
            $payload['carouselFormat'] = $this->resolveCarouselFormat($template);
            $payload['carousel'] = $this->buildCarouselCards($template);
            $body = $this->buildBody($template);
            if ($body) {
                $payload['body'] = $body;
            }
        } else {
            $header = $this->buildHeader($template);
            if ($header) {
                $payload['header'] = $header;
            }

            $body = $this->buildBody($template);
            if ($body) {
                $payload['body'] = $body;
            }

            $footer = $this->buildFooter($template);
            if ($footer) {
                $payload['footer'] = $footer;
            }

            $buttons = $this->buildButtons($template->getButtons());
            if (!empty($buttons)) {
                $payload['buttons'] = $buttons;
            }
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
                $body['example'] = [
                    'body_text' => [$examples]
                ];
            } elseif (!empty($result['params'])) {
                $body['example'] = [
                    'body_text' => [$result['params']]
                ];
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

        if (isset($buttonsData['type'])) {
            $buttonsData = [$buttonsData];
        }

        $buttons = [];
        foreach ($buttonsData as $btn) {
            if (!is_array($btn) || empty($btn['type'])) {
                continue;
            }

            $button = ['type' => $btn['type']];

            if ($btn['type'] === 'URL') {
                $button['text'] = $btn['text'] ?? '';
                $urlResult = $this->processTextVariables((string)($btn['url'] ?? ($btn['value'] ?? '')));
                $button['url'] = $urlResult['text'];
                if (!empty($urlResult['params'])) {
                    $button['example'] = $urlResult['params'];
                }
            } elseif ($btn['type'] === 'PHONE_NUMBER') {
                $button['text'] = $btn['text'] ?? '';
                $button['value'] = $btn['phone_number'] ?? ($btn['value'] ?? '');
            } elseif ($btn['type'] === 'QUICK_REPLY') {
                $button['text'] = $btn['text'] ?? '';
            } elseif ($btn['type'] === 'OTP') {
                $button['otp_type'] = $btn['otp_type'] ?? '';
                // For OTP, text might not be required or could be specific
            } elseif ($btn['type'] === 'COPY_CODE') {
                // Copy code button does not need text
            }

            $buttons[] = $button;
        }

        return $buttons;
    }

    protected function buildCarouselCards(TemplateInterface $template): array
    {
        $cardsStr = $template->getCarouselCards();
        $cardsData = $cardsStr ? json_decode($cardsStr, true) : [];
        $cards = [];

        foreach ($cardsData as $cardData) {
            $card = [];

            // Header (Media)
            $headerFormat = strtoupper((string)($cardData['header_format'] ?? ''));
            if (in_array($headerFormat, ['IMAGE', 'VIDEO'], true) && !empty($cardData['header_handle'])) {
                $card['header'] = [
                    'type' => 'HEADER',
                    'format' => $headerFormat,
                    'media' => [
                        'document_id' => $cardData['header_handle']
                    ]
                ];
            }

            // Body
            if (!empty($cardData['body'])) {
                $bodyResult = $this->processTextVariables((string)$cardData['body']);
                $card['body'] = [
                    'type' => 'BODY',
                    'format' => 'TEXT',
                    'text' => $bodyResult['text']
                ];
                if (!empty($bodyResult['params'])) {
                    $card['body']['example'] = [
                        'body_text' => [$bodyResult['params']]
                    ];
                }
            }

            // Buttons
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

    protected function resolveCarouselFormat(TemplateInterface $template): string
    {
        $storedFormat = strtoupper((string)$template->getCarouselFormat());
        if (in_array($storedFormat, ['IMAGE', 'VIDEO'], true)) {
            return $storedFormat;
        }

        $cardsData = json_decode((string)$template->getCarouselCards(), true);
        if (is_array($cardsData)) {
            foreach ($cardsData as $cardData) {
                $cardFormat = strtoupper((string)($cardData['header_format'] ?? ''));
                if (in_array($cardFormat, ['IMAGE', 'VIDEO'], true)) {
                    return $cardFormat;
                }
            }
        }

        return 'IMAGE';
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

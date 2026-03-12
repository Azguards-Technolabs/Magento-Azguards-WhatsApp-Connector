<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Model\Service;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterface;

class MetaTemplatePayloadBuilder
{
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
            'components' => $this->buildComponents($template)
        ];

        return $payload;
    }

    protected function buildComponents(TemplateInterface $template): array
    {
        $components = [];

        if ($template->getTemplateType() === 'CAROUSEL') {
            $components[] = $this->buildCarousel($template);
            return $components;
        }

        // Header
        $header = $this->buildHeader($template);
        if ($header) {
            $components[] = $header;
        }

        // Body
        $body = $this->buildBody($template);
        if ($body) {
            $components[] = $body;
        }

        // Footer
        $footer = $this->buildFooter($template);
        if ($footer) {
            $components[] = $footer;
        }

        // Buttons
        $buttons = $this->buildButtons($template->getButtons());
        if (!empty($buttons)) {
            $components[] = [
                'type' => 'BUTTONS',
                'buttons' => $buttons
            ];
        }

        // Special Template Handling
        if ($template->getTemplateType() === 'LTO' && $template->getLimitedTimeOffer()) {
            $ltoData = json_decode($template->getLimitedTimeOffer(), true);
            if ($ltoData) {
                // Adjust LTO implementation according to Meta specs
                $components[] = [
                    'type' => 'LIMITED_TIME_OFFER',
                    'text' => $ltoData['text'],
                    'expiration_minutes' => $ltoData['expiration_minutes']
                ];
            }
        }

        return $components;
    }

    protected function buildHeader(TemplateInterface $template): ?array
    {
        $format = $template->getHeaderFormat() ?: 'TEXT';

        if ($format === 'TEXT' && $template->getHeader()) {
            return [
                'type' => 'HEADER',
                'format' => 'TEXT',
                'text' => $template->getHeader()
            ];
        }

        if (in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT'])) {
            $handle = $template->getHeaderHandle();
            if ($handle) {
                return [
                    'type' => 'HEADER',
                    'format' => $format,
                    'example' => [
                        'header_handle' => [$handle]
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
            $body = [
                'type' => 'BODY',
                'text' => $text
            ];

            // Try to extract body_examples_json directly if the data structure has it.
            // Since it is not in the TemplateInterface yet, fallback to fetching from GetData or generate
            // For now, assume it might be passed via $template->getData('body_examples_json')
            $examplesStr = $template->getData('body_examples_json');
            $examples = $examplesStr ? json_decode($examplesStr, true) : [];

            if (empty($examples)) {
                // Attach dummy examples for placeholders if not provided
                $examples = $this->attachExamples($text);
            }

            if (!empty($examples)) {
                $body['example'] = [
                    'body_text' => [$examples]
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

        $buttons = [];
        foreach ($buttonsData as $btn) {
            $button = ['type' => $btn['type']];

            if ($btn['type'] === 'URL') {
                $button['text'] = $btn['text'];
                $button['url'] = $btn['url'];
            } elseif ($btn['type'] === 'PHONE_NUMBER') {
                $button['text'] = $btn['text'];
                $button['phone_number'] = $btn['phone_number'];
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
                $components[] = [
                    'type' => 'HEADER',
                    'format' => $headerFormat,
                    'example' => [
                        'header_handle' => [$cardData['header_handle']]
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

    protected function attachExamples(string $text): array
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        $examples = [];

        if (!empty($matches[1])) {
            $count = count($matches[1]);
            for ($i = 1; $i <= $count; $i++) {
                $examples[] = 'sample_' . $i;
            }
        }

        return $examples;
    }
}

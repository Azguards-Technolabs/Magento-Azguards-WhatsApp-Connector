<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Examples;

use Company\WhatsappTemplate\Api\Data\TemplateInterfaceFactory;
use Company\WhatsappTemplate\Service\TemplateSaveService;

/**
 * Example class showing how to use TemplateSaveService to save a full template structure.
 */
class SaveTemplateExample
{
    /**
     * @var TemplateInterfaceFactory
     */
    private $templateFactory;

    /**
     * @var TemplateSaveService
     */
    private $templateSaveService;

    /**
     * @param TemplateInterfaceFactory $templateFactory
     * @param TemplateSaveService $templateSaveService
     */
    public function __construct(
        TemplateInterfaceFactory $templateFactory,
        TemplateSaveService $templateSaveService
    ) {
        $this->templateFactory = $templateFactory;
        $this->templateSaveService = $templateSaveService;
    }

    /**
     * Example method to save a complex template.
     *
     * @return void
     */
    public function execute()
    {
        // 1. Initialize the Template model
        $template = $this->templateFactory->create();
        $template->setName('order_confirmation_v2');
        $template->setLanguageId('en_US');
        $template->setCategoryId('TRANSACTIONAL');
        $template->setType('TEXT');
        $template->setStatus('APPROVED');
        $template->setCreatedBy('Admin');

        // 2. Prepare Components Data (including nested Variables)
        $componentsData = [
            [
                'component_type' => 'HEADER',
                'component_format' => 'TEXT',
                'component_data' => 'Order Confirmation',
                'order' => 1,
                'variables' => []
            ],
            [
                'component_type' => 'BODY',
                'component_format' => 'TEXT',
                'component_data' => 'Hello {{1}}, thank you for your order #{{2}}!',
                'order' => 2,
                'variables' => [
                    [
                        'variable_position' => 1,
                        'type' => 'TEXT',
                        'default_value' => 'Customer',
                        'parameter_format' => 'STRING'
                    ],
                    [
                        'variable_position' => 2,
                        'type' => 'TEXT',
                        'default_value' => '0000',
                        'parameter_format' => 'STRING'
                    ]
                ]
            ],
            [
                'component_type' => 'FOOTER',
                'component_format' => 'TEXT',
                'component_data' => 'If you have any questions, contact us.',
                'order' => 3,
                'variables' => []
            ]
        ];

        // 3. Prepare Buttons Data
        $buttonsData = [
            [
                'type' => 'URL',
                'text' => 'View Order',
                'url' => 'https://example.com/orders/{{1}}',
                'order' => 1
            ],
            [
                'type' => 'PHONE_NUMBER',
                'text' => 'Call Support',
                'phone_number' => '+1234567890',
                'order' => 2
            ]
        ];

        try {
            // 4. Call the Save Service
            $savedTemplate = $this->templateSaveService->saveFullTemplate(
                $template,
                $componentsData,
                $buttonsData
            );

            // Success: $savedTemplate now has an ID and all related records are saved.
        } catch (\Exception $e) {
            // Handle error (e.g., log it)
        }
    }
}

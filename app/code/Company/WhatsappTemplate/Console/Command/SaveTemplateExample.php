<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Company\WhatsappTemplate\Model\Service\TemplateSaveService;

class SaveTemplateExample extends Command
{
    private $templateSaveService;

    public function __construct(
        TemplateSaveService $templateSaveService,
        string $name = null
    ) {
        $this->templateSaveService = $templateSaveService;
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('company:whatsapp:example-save')
             ->setDescription('Demonstrates saving a WhatsApp Template with components, variables, and buttons.');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Starting Template Save Example...</info>');

        $templateData = [
            'name' => 'order_shipped_v1',
            'language_id' => 'en_US',
            'category_id' => 'UTILITY',
            'type' => 'TEXT',
            'status' => 'APPROVED',
            'business_id' => 12345
        ];

        $componentsData = [
            [
                'component_type' => 'BODY',
                'component_format' => 'TEXT',
                'component_data' => 'Your order {{1}} is out for delivery. Tracking link: {{2}}',
                'sort_order' => 1,
                'variables' => [
                    [
                        'variable_position' => 1,
                        'type' => 'text',
                        'default_value' => 'ORDER_ID_FALLBACK',
                        'parameter_format' => 'numeric'
                    ],
                    [
                        'variable_position' => 2,
                        'type' => 'url',
                        'default_value' => 'https://example.com/track',
                        'parameter_format' => 'url'
                    ]
                ]
            ],
            [
                'component_type' => 'HEADER',
                'component_format' => 'TEXT',
                'component_data' => 'Order Update',
                'sort_order' => 0
            ]
        ];

        $buttonsData = [
            [
                'type' => 'url',
                'sort_order' => 1,
                'text' => 'Track Order',
                'url' => 'https://example.com/track/{{1}}'
            ],
            [
                'type' => 'quick_reply',
                'sort_order' => 2,
                'text' => 'Stop Updates'
            ]
        ];

        try {
            $template = $this->templateSaveService->saveTemplateWithRelations(
                $templateData,
                $componentsData,
                $buttonsData
            );
            $output->writeln('<info>Successfully saved Template ID: ' . $template->getId() . '</info>');
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }
}

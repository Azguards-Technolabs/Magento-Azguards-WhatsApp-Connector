<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Setup\Patch\Data;

use Azguards\WhatsAppConnect\Api\Data\TemplateInterfaceFactory;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Data patch to add prebuilt WhatsApp templates for marketing and engagement.
 */
class AddPrebuiltTemplates implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var TemplateInterfaceFactory
     */
    private $templateFactory;

    /**
     * @var TemplateRepositoryInterface
     */
    private $templateRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param TemplateInterfaceFactory $templateFactory
     * @param TemplateRepositoryInterface $templateRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        TemplateInterfaceFactory $templateFactory,
        TemplateRepositoryInterface $templateRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->templateFactory = $templateFactory;
        $this->templateRepository = $templateRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $templates = [
            [
                'template_name' => 'abandoned_cart_recovery',
                'template_type' => 'MEDIA',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'IMAGE',
                'body' => "Hi {{1}}, 🛒\n\nWe noticed you left something behind! Your favorite items are still waiting in your cart.\n\nComplete your purchase today and use code *SAVE10* for an exclusive 10% discount. Don't let them slip away!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Visit Cart', 'value' => 'https://{{store_url}}/cart'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Stop Promotions', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John'])
            ],
            [
                'template_name' => 'flash_sale_urgency',
                'template_type' => 'MEDIA',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'IMAGE',
                'body' => "Hello {{1}}! 🚨\n\nOur *{{2}} Sale* is now LIVE! Get up to *{{3}}% OFF* on all categories.\n\nThis offer is valid for the next 24 hours only. Grab your favorites before the clock runs out!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Shop the Sale', 'value' => 'https://{{store_url}}/sale'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Stop Promotions', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John', 'Weekend', '50'])
            ],
            [
                'template_name' => 'new_collection_launch',
                'template_type' => 'MEDIA',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'IMAGE',
                'body' => "Big News, {{1}}! 🚀\n\nWe’ve just launched our new *{{2}} Collection*. From modern designs to classic essentials, we have something special for you.\n\nBe the first to explore the new arrivals!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Explore Collection', 'value' => 'https://{{store_url}}/new'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Stop Promotions', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John', 'Summer'])
            ],
            [
                'template_name' => 'customer_winback',
                'template_type' => 'TEXT',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'NONE',
                'body' => "We miss you, {{1}}! 👋\n\nIt’s been a while since your last visit. To welcome you back, we’re offering *FREE SHIPPING* on your next order.\n\nCome see what’s new in our store today!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Shop Now', 'value' => 'https://{{store_url}}'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Stop Promotions', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John'])
            ],
            [
                'template_name' => 'hyperlocal_service_promo',
                'template_type' => 'MEDIA',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'IMAGE',
                'body' => "Freshness delivered to your door, {{1}}! 📍\n\nGet your daily essentials delivered within *{{2}} minutes*. We ensure 100% quality and contact-less delivery right to your doorstep.\n\nOrder now and experience the fastest service in town!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Order Now', 'value' => 'https://{{store_url}}'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Stop Promotions', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John', '30'])
            ],
            [
                'template_name' => 'feedback_review_request',
                'template_type' => 'TEXT',
                'template_category' => 'Marketing',
                'language' => 'en_US',
                'status' => 'PENDING',
                'header_format' => 'NONE',
                'body' => "Hi {{1}}, we hope you loved your recent order! 🌟\n\nYour feedback helps us grow. Could you spare a minute to rate your experience? As a thank you, you’ll receive a surprise gift in your next order!",
                'buttons' => json_encode([
                    ['type' => 'URL', 'text' => 'Leave a Review', 'value' => 'https://{{store_url}}/review'],
                    ['type' => 'QUICK_REPLY', 'text' => 'Talk to Support', 'value' => '']
                ]),
                'body_examples_json' => json_encode(['John'])
            ]
        ];

        foreach ($templates as $data) {
            try {
                $templateName = $data['template_name'];

                // Check if template already exists to avoid duplicates
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('template_name', $templateName)
                    ->create();
                $existingTemplates = $this->templateRepository->getList($searchCriteria);

                if ($existingTemplates->getTotalCount() > 0) {
                    continue;
                }

                $template = $this->templateFactory->create();
                $template->setData($data);
                $this->templateRepository->save($template);
            } catch (\Exception $e) {
                $this->logger->error('Error adding prebuilt WhatsApp template: ' . $e->getMessage());
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

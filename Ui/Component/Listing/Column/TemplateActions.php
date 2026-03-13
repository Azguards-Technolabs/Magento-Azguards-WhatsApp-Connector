<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TemplateActions extends Column
{
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    $item[$name]['edit'] = [
                        'href' => $this->urlBuilder->getUrl('whatsappconnect/template/edit', ['id' => $item['entity_id']]),
                        'label' => __('Edit')
                    ];
                    $item[$name]['preview'] = [
                        'href' => $this->urlBuilder->getUrl('whatsappconnect/template/preview', ['id' => $item['entity_id']]),
                        'label' => __('Preview')
                    ];
                    $item[$name]['delete'] = [
                        'href' => $this->urlBuilder->getUrl('whatsappconnect/template/delete', ['id' => $item['entity_id']]),
                        'label' => __('Delete'),
                        'confirm' => [
                            'title' => __('Delete %1', $item['template_name'] ?? ''),
                            'message' => __('Are you sure you want to delete a %1 record?', $item['template_name'] ?? '')
                        ]
                    ];
                }
            }
        }
        return $dataSource;
    }
}

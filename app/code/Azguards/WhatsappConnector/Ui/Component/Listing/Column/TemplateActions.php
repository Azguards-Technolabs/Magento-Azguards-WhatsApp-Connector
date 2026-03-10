<?php
namespace Azguards\WhatsappConnector\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class TemplateActions extends Column
{
    protected $urlBuilder;

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
                if (isset($item['template_id'])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                'whatsappconnector/template/edit',
                                ['template_id' => $item['template_id']]
                            ),
                            'label' => __('Edit')
                        ],
                        'preview' => [
                            'href' => $this->urlBuilder->getUrl(
                                'whatsappconnector/template/preview',
                                ['template_id' => $item['template_id']]
                            ),
                            'label' => __('Preview'),
                            'target' => '_blank'
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                'whatsappconnector/template/delete',
                                ['template_id' => $item['template_id']]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete Template'),
                                'message' => __('Are you sure you want to delete this template from Magento and WhatsApp ERP?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}

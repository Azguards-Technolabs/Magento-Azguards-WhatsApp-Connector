<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Block\Adminhtml\Template\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DuplicateButton implements ButtonProviderInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Return duplicate button configuration.
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        $id = $this->context->getRequest()->getParam('id');
        $isDuplicate = $this->context->getRequest()->getParam('is_duplicate');

        if ($id && !$isDuplicate) {
            $data = [
                'label' => __('Duplicate'),
                'class' => 'duplicate',
                'on_click' => sprintf("location.href = '%s';", $this->getDuplicateUrl()),
                'sort_order' => 30,
            ];
        }
        return $data;
    }

    /**
     * Return the duplicate URL.
     *
     * @return string
     */
    private function getDuplicateUrl()
    {
        return $this->context->getUrlBuilder()->getUrl(
            '*/*/duplicate',
            ['id' => $this->context->getRequest()->getParam('id')]
        );
    }
}

<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Azguards\WhatsAppConnect\Model\ResourceModel\Template\CollectionFactory;
use Azguards\WhatsAppConnect\Model\Service\TemplateService;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $filter;
    private $collectionFactory;
    private $templateService;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        TemplateService $templateService
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->templateService = $templateService;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;

        foreach ($collection as $item) {
            try {
                $this->templateService->deleteTemplate((int)$item->getId());
                $deleted++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Error deleting template with ID %1: %2', $item->getId(), $e->getMessage())
                );
            }
        }

        if ($deleted) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $deleted)
            );
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}

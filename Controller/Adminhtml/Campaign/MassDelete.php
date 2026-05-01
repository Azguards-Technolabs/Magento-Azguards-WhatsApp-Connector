<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Azguards\WhatsAppConnect\Model\ResourceModel\Campaign\CollectionFactory;
use Azguards\WhatsAppConnect\Model\Service\CampaignService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var CampaignService
     */
    private CampaignService $campaignService;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CampaignService $campaignService
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        CampaignService $campaignService
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->campaignService = $campaignService;
    }

    /**
     * Delete selected campaigns from the admin grid.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deleted = 0;

            foreach ($collection as $campaign) {
                $this->campaignService->deleteById((int)$campaign->getId());
                $deleted++;
            }

            if ($deleted > 0) {
                $this->messageManager->addSuccessMessage(__('A total of %1 campaign(s) have been deleted.', $deleted));
            } else {
                $this->messageManager->addNoticeMessage(__('No campaigns were deleted.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete selected campaigns. %1', $e->getMessage()));
        }

        return $resultRedirect;
    }
}

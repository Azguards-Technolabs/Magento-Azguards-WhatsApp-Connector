<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class SearchCustomers extends Action
{
    public const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::campaigns';

    private JsonFactory $resultJsonFactory;
    private CollectionFactory $customerCollectionFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CollectionFactory $customerCollectionFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $searchTerm = trim((string)$this->getRequest()->getParam('q', ''));
        $idsParam = $this->getRequest()->getParam('ids');
        $page = (int)$this->getRequest()->getParam('page', 1);
        $pageSize = 20;

        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'whatsapp_phone_number']);
        
        // Filter by WhatsApp sync status (Senior Level Requirement)
        $collection->addAttributeToFilter('whatsapp_sync_status', 1);

        $ids = [];
        if ($idsParam !== null && $idsParam !== '') {
            $ids = is_array($idsParam) ? $idsParam : explode(',', (string)$idsParam);
            $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        }

        if ($ids !== []) {
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
        } elseif ($searchTerm !== '') {
            $collection->addAttributeToFilter([
                ['attribute' => 'firstname', 'like' => '%' . $searchTerm . '%'],
                ['attribute' => 'lastname', 'like' => '%' . $searchTerm . '%'],
                ['attribute' => 'email', 'like' => '%' . $searchTerm . '%'],
                ['attribute' => 'whatsapp_phone_number', 'like' => '%' . $searchTerm . '%'],
            ]);
        }
        
        if ($ids === []) {
            $collection->setCurPage($page);
            $collection->setPageSize($pageSize);
        } else {
            $collection->setCurPage(1);
            $collection->setPageSize(count($ids));
        }

        $items = [];
        foreach ($collection as $customer) {
            $name = trim($customer->getFirstname() . ' ' . $customer->getLastname());
            $email = (string)$customer->getEmail();
            $phone = (string)$customer->getData('whatsapp_phone_number');

            $labelParts = [];
            if ($name !== '') {
                $labelParts[] = $name;
            }
            if ($phone !== '') {
                $labelParts[] = $phone;
            } elseif ($email !== '') {
                $labelParts[] = $email;
            }

            $label = $labelParts !== [] ? implode(' — ', $labelParts) : ('Customer #' . $customer->getId());

            $items[] = [
                'id' => $customer->getId(),
                'text' => $label,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ];
        }

        $totalCount = $collection->getSize();
        $hasMore = ($page * $pageSize) < $totalCount;

        return $result->setData([
            'results' => $items,
            'pagination' => ['more' => $ids === [] ? $hasMore : false]
        ]);
    }
}

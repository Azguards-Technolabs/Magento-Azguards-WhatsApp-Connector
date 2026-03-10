<?php
declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Controller\Adminhtml\Template;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Azguards\WhatsAppConnect\Api\TemplateRepositoryInterface;

class Preview extends Action
{
    const ADMIN_RESOURCE = 'Azguards_WhatsAppConnect::templates';

    private $templateRepository;

    public function __construct(
        Context $context,
        TemplateRepositoryInterface $templateRepository
    ) {
        parent::__construct($context);
        $this->templateRepository = $templateRepository;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('This template no longer exists.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $template = $this->templateRepository->getById((int)$id);

            // Generate dummy preview content
            $html = "<h3>WhatsApp Template Preview</h3><hr/>";
            $html .= "<div style='max-width:400px; border:1px solid #ccc; padding:15px; border-radius:10px; font-family: sans-serif;'>";

            if ($template->getHeader()) {
                $header = str_replace('{{1}}', '<b>[Header Example Variable]</b>', $template->getHeader());
                $html .= "<div style='font-weight:bold; margin-bottom:10px;'>{$header}</div>";
            }

            $body = $template->getBody();
            $body = str_replace('{{1}}', '<b>[Body Var 1]</b>', $body);
            $body = str_replace('{{2}}', '<b>[Body Var 2]</b>', $body);
            $body = str_replace('{{3}}', '<b>[Body Var 3]</b>', $body);
            $html .= "<div style='margin-bottom:10px; white-space: pre-wrap;'>{$body}</div>";

            if ($template->getFooter()) {
                $html .= "<div style='color:gray; font-size:12px; margin-bottom:10px;'>{$template->getFooter()}</div>";
            }

            if ($template->getButtonType()) {
                $html .= "<div style='text-align:center; padding:10px; border-top:1px solid #eee;'>";
                $html .= "<span style='color:#007bff; font-weight:bold; cursor:pointer;'>{$template->getButtonText()}</span>";
                $html .= "</div>";
            }

            $html .= "</div>";

            /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
            $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $resultRaw->setContents($html);
            return $resultRaw;

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
    }
}

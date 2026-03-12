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

    /**
     * Preview constructor
     *
     * @param Context $context
     * @param TemplateRepositoryInterface $templateRepository
     */
    public function __construct(
        Context $context,
        TemplateRepositoryInterface $templateRepository
    ) {
        parent::__construct($context);
        $this->templateRepository = $templateRepository;
    }

    /**
     * Generate template preview HTML
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
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

            // Generate WhatsApp-like preview content
            $html = "<div style='background-color:#e5ddd5; padding:20px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>";
            $html .= "<h3 style='margin-top:0;'>WhatsApp Template Preview</h3><hr style='border: 0; border-top: 1px solid #ccc; margin: 15px 0;'/>";
            $html .= "<div style='max-width:300px; background-color:#fff; padding:10px; border-radius:7.5px; box-shadow: 0 1px 0.5px rgba(0,0,0,0.13); position:relative;'>";

            // Header (Text or Image)
            if ($template->getTemplateType() === 'IMAGE' && $template->getHeaderImage()) {
                $imgUrl = $template->getHeaderImage();
                if (filter_var($imgUrl, FILTER_VALIDATE_URL)) {
                    $html .= "<div style='margin: -10px -10px 10px -10px;'><img src='{$imgUrl}' style='width:100%; border-radius:7.5px 7.5px 0 0; display:block;' /></div>";
                } else {
                    $html .= "<div style='background-color:#dfe5e7; height:150px; border-radius:7.5px; display:flex; align-items:center; justify-content:center; margin-bottom:10px; color:#54656f; font-size:14px; text-align:center; padding: 0 20px;'>";
                    $html .= "<span>🖼️ Image Header<br/><small>(Media ID: {$imgUrl})</small></span>";
                    $html .= "</div>";
                }
            } elseif ($template->getHeader()) {
                $header = preg_replace('/{{[^}]+}}/', '<b style="color:#00a884;">[Header Variable]</b>', $template->getHeader());
                $html .= "<div style='font-weight:bold; font-size:16px; margin-bottom:5px; color:#111b21;'>{$header}</div>";
            }

            // Body
            $body = $template->getBody();
            // Replace any {{variable}} or {{1}} with bold placeholder
            $body = preg_replace('/{{[^}]+}}/', '<b style="color:#00a884;">[Variable]</b>', $body);
            $html .= "<div style='font-size:14.2px; line-height:1.4; color:#111b21; white-space: pre-wrap; margin-bottom:5px;'>{$body}</div>";

            // Footer
            if ($template->getFooter()) {
                $html .= "<div style='color:#667781; font-size:12px; margin-bottom:10px;'>{$template->getFooter()}</div>";
            }

            // Buttons
            $buttonsJson = $template->getButtons();
            if ($buttonsJson) {
                $buttons = json_decode((string)$buttonsJson, true);
                if (is_array($buttons) && !empty($buttons)) {
                    $html .= "<div style='border-top:1px solid #f0f2f5; margin: 5px -10px -10px -10px;'>";
                    foreach ($buttons as $btn) {
                        $icon = "";
                        if (isset($btn['type'])) {
                            if ($btn['type'] === 'url') $icon = "🔗 ";
                            if ($btn['type'] === 'phone') $icon = "📞 ";
                        }
                        $html .= "<div style='text-align:center; padding:10px; border-bottom:1px solid #f0f2f5; color:#00a884; font-weight:500; font-size:14px; cursor:pointer;'>";
                        $html .= $icon . $btn['text'];
                        $html .= "</div>";
                    }
                    $html .= "</div>";
                }
            }

            $html .= "</div>";
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

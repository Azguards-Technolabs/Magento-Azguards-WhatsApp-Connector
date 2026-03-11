<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\TemplateRepositoryInterface;
use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Company\WhatsappTemplate\Api\Data\TemplateInterfaceFactory;
use Company\WhatsappTemplate\Model\ResourceModel\Template as TemplateResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

class TemplateRepository implements TemplateRepositoryInterface
{
    private $resource;
    private $templateFactory;

    public function __construct(
        TemplateResource $resource,
        TemplateInterfaceFactory $templateFactory
    ) {
        $this->resource = $resource;
        $this->templateFactory = $templateFactory;
    }

    public function save(TemplateInterface $template): TemplateInterface
    {
        try {
            $this->resource->save($template);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $template;
    }

    public function getById(int $id): TemplateInterface
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $id);
        if (!$template->getId()) {
            throw new NoSuchEntityException(__('The template with ID "%1" doesn\'t exist.', $id));
        }
        return $template;
    }

    public function delete(TemplateInterface $template): bool
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}

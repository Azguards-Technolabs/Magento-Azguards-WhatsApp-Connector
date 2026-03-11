<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Model;

use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Company\WhatsappTemplate\Api\TemplateRepositoryInterface;
use Company\WhatsappTemplate\Model\ResourceModel\Template as TemplateResource;
use Company\WhatsappTemplate\Model\TemplateFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class TemplateRepository
 */
class TemplateRepository implements TemplateRepositoryInterface
{
    /**
     * @var TemplateResource
     */
    private $resource;

    /**
     * @var TemplateFactory
     */
    private $templateFactory;

    /**
     * @param TemplateResource $resource
     * @param TemplateFactory $templateFactory
     */
    public function __construct(
        TemplateResource $resource,
        TemplateFactory $templateFactory
    ) {
        $this->resource = $resource;
        $this->templateFactory = $templateFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(TemplateInterface $template)
    {
        try {
            $this->resource->save($template);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the template: %1', $exception->getMessage())
            );
        }
        return $template;
    }

    /**
     * @inheritdoc
     */
    public function getById($id)
    {
        $template = $this->templateFactory->create();
        $this->resource->load($template, $id);
        if (!$template->getId()) {
            throw new NoSuchEntityException(__('Template with ID "%1" does not exist.', $id));
        }
        return $template;
    }

    /**
     * @inheritdoc
     */
    public function delete(TemplateInterface $template)
    {
        try {
            $this->resource->delete($template);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the template: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}

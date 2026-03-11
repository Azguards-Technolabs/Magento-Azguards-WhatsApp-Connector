<?php
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api;

use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

interface TemplateRepositoryInterface
{
    /**
     * @param TemplateInterface $template
     * @return TemplateInterface
     * @throws CouldNotSaveException
     */
    public function save(TemplateInterface $template): TemplateInterface;

    /**
     * @param int $id
     * @return TemplateInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): TemplateInterface;

    /**
     * @param TemplateInterface $template
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TemplateInterface $template): bool;

    /**
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool;
}

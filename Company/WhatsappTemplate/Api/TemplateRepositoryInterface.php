<?php
/**
 * Copyright © Company, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Company\WhatsappTemplate\Api;

use Company\WhatsappTemplate\Api\Data\TemplateInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface TemplateRepositoryInterface
 */
interface TemplateRepositoryInterface
{
    /**
     * @param TemplateInterface $template
     * @return TemplateInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(TemplateInterface $template);

    /**
     * @param int $id
     * @return TemplateInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param TemplateInterface $template
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(TemplateInterface $template);

    /**
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}

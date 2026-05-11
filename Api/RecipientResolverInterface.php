<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api;

interface RecipientResolverInterface
{
    /**
     * Resolve mobile number dynamically based on entity/document type.
     *
     * @param object $entity
     * @return string|null
     */
    public function resolveByEntity(object $entity): ?string;
}
<?php

declare(strict_types=1);

namespace Azguards\WhatsAppConnect\Api;

/**
 * Interface for resolving recipient mobile number based on entity type.
 */
interface RecipientResolverInterface
{
    /**
     * Resolve recipient mobile number based on entity.
     *
     * @param object $entity
     * @return string|null
     */
    public function resolveByEntity(object $entity): ?string;
}

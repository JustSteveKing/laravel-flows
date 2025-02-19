<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Contracts;

interface FlowCondition
{
    /**
     * Evaluate the condition for the given payload.
     *
     * @param mixed $payload
     * @return bool
     */
    public function __invoke(mixed $payload): bool;
}

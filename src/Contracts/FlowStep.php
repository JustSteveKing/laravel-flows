<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Contracts;

use Closure;

interface FlowStep
{
    /**
     * Process the payload and pass it to the next step.
     *
     * @param mixed $payload
     * @param Closure $next
     * @return mixed
     */
    public function handle(mixed $payload, Closure $next): mixed;
}

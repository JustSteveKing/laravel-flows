<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use JustSteveKing\Flows\Contracts\FlowCondition;

class DummyCondition implements FlowCondition
{
    public function __invoke(mixed $payload): bool
    {
        // Return true if the payload contains "run".
        return is_string($payload) && str_contains($payload, 'run');
    }
}

<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use JustSteveKing\Flows\Contracts\FlowCondition;

final class IsTrue implements FlowCondition
{
    public function __invoke(mixed $payload): bool
    {
        return true;
    }
}

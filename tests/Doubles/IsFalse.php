<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use JustSteveKing\Flows\Contracts\FlowCondition;

final class IsFalse implements FlowCondition
{
    public function __invoke(mixed $payload): bool
    {
        return false;
    }
}

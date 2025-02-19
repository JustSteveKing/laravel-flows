<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use Closure;
use JustSteveKing\Flows\Contracts\FlowStep;

class DummyStep implements FlowStep
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        // For testing, simply append " foo" to the payload.
        return $next($payload . ' foo');
    }
}

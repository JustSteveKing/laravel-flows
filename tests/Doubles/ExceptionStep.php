<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use Closure;
use Exception;
use JustSteveKing\Flows\Contracts\FlowStep;

final class ExceptionStep implements FlowStep
{
    /**
     * @param mixed $payload
     * @param Closure $next
     * @return mixed
     * @throws Exception@@@@
     */
    public function handle(mixed $payload, Closure $next): mixed
    {
        throw new Exception('Step failed');
    }
}

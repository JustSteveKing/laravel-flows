<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Steps;

use Closure;
use Illuminate\Support\Facades\Validator;
use JustSteveKing\Flows\Contracts\FlowStep;
use JustSteveKing\Flows\Contracts\ValidatingStep;

class ValidationStep implements FlowStep
{
    public function __construct(private ValidatingStep $step)
    {
    }

    public function handle(mixed $payload, Closure $next): mixed
    {
        Validator::make(
            (array) $payload,
            $this->step->rules($payload)
        )->validate();

        return $this->step->handle($payload, $next);
    }
}

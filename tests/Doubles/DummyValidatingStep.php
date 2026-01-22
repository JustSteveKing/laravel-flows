<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use Closure;
use JustSteveKing\Flows\Contracts\ValidatingStep;

class DummyValidatingStep implements ValidatingStep
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        $payload['validated'] = true;
        return $next($payload);
    }

    public function rules(mixed $payload): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}

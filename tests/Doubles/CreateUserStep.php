<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use Closure;
use Illuminate\Support\Facades\DB;
use JustSteveKing\Flows\Contracts\FlowStep;

class CreateUserStep implements FlowStep
{
    public function handle(mixed $payload, Closure $next): mixed
    {
        DB::table('users')->insert($payload);

        return $next($payload);
    }
}

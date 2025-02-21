<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests\Doubles;

use JustSteveKing\Flows\Flow;

final class DummyReusableFlow
{
    public function __invoke(Flow $flow): void
    {
        $flow->run(fn(mixed $payload) => $payload . ' dummy reusable flow');
    }
}

<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Contracts;

interface Workflow
{
    /**
     * Run the workflow with the given payload.
     *
     * @param mixed $payload
     * @return mixed
     */
    public static function run(mixed $payload): mixed;
}

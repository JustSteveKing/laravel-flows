<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Contracts;

interface ValidatingStep extends FlowStep
{
    /**
     * Get the validation rules for the step.
     *
     * @param  mixed $payload
     * @return array
     */
    public function rules(mixed $payload): array;
}

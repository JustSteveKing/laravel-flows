<?php

declare(strict_types=1);

namespace JustSteveKing\Flows;

use Closure;
use Illuminate\Support\Facades\Pipeline;
use JustSteveKing\Flows\Contracts\FlowCondition;
use JustSteveKing\Flows\Contracts\FlowStep;

final class Flow
{
    /**
     * @param array<int,class-string<FlowStep>|Closure|FlowStep> $steps
     */
    public function __construct(
        protected array $steps = [],
    ) {}

    /**
     * @return Flow
     */
    public static function start(): Flow
    {
        return new Flow();
    }

    /**
     * @param class-string<FlowStep>|Closure $action
     * @return $this
     */
    public function run(string|Closure $action): Flow
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * @param class-string<FlowCondition> $condition
     * @param callable $callback
     * @return $this
     */
    public function branch(string $condition, callable $callback): Flow
    {
        $this->steps[] = static function (mixed $payload, Closure $next) use ($condition, $callback) {
            $checker = resolve($condition);

            if ($checker($payload)) {
                $payload = $callback($payload);
            }

            return $next($payload);
        };

        return $this;
    }

    /**
     * Add a step that will only run if the condition is met.
     *
     * @param callable $condition A callable that receives the payload and returns a boolean.
     * @param class-string<FlowStep> $action
     * @return $this
     */
    public function runIf(callable $condition, string $action): self
    {
        $this->steps[] = static function ($payload, $next) use ($condition, $action) {
            if (!$condition($payload)) {
                return $next($payload);
            }
            return resolve($action)->handle($payload, $next);
        };

        return $this;
    }

    /**
     * @param class-string<FlowStep> $action
     * @return $this
     */
    public function chain(string $action): Flow
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * @param mixed $payload
     * @return mixed
     */
    public function execute(mixed $payload): mixed
    {
        return Pipeline::send(
            passable: $payload,
        )->through(
            pipes: $this->steps,
        )->thenReturn();
    }
}

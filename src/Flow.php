<?php

declare(strict_types=1);

namespace JustSteveKing\Flows;

use Closure;
use Illuminate\Support\Facades\Pipeline;
use JustSteveKing\Flows\Contracts\FlowCondition;
use JustSteveKing\Flows\Contracts\FlowStep;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class Flow
{
    /**
     * Flow constructor.
     *
     * @param array<int, class-string<FlowStep>|Closure> $steps
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private array $steps = [],
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Create a new Flow instance.
     *
     * @return Flow
     */
    public static function start(): Flow
    {
        return new Flow();
    }

    /**
     * Set a logger for debugging.
     *
     * @param LoggerInterface $logger
     * @return Flow
     */
    public function debug(LoggerInterface $logger): Flow
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Add a step to the workflow.
     *
     * @param class-string<FlowStep>|Closure $action
     * @return Flow
     */
    public function run(string|Closure $action): Flow
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * Add a conditional branch to the workflow.
     *
     * @param class-string<FlowCondition> $condition
     * @param callable $callback
     * @return Flow
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
     * Add a step that runs only if the given condition returns true.
     *
     * @param callable $condition A callable that receives the payload and returns a boolean.
     * @param class-string<FlowStep> $action
     * @return Flow
     */
    public function runIf(callable $condition, string $action): Flow
    {
        $this->steps[] = function (mixed $payload, Closure $next) use ($condition, $action) {
            if ( ! $condition($payload)) {
                return $next($payload);
            }

            return $this->resolveStep(
                step: $action,
            )->handle($payload, $next);
        };

        return $this;
    }

    /**
     * Add a chained step to the workflow.
     *
     * @param class-string<FlowStep> $action
     * @return Flow
     */
    public function chain(string $action): Flow
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * Add error handling to the workflow.
     *
     * @param callable $errorHandler
     * @return Flow
     */
    public function catch(callable $errorHandler): Flow
    {
        $this->steps[] = static function (mixed $payload, Closure $next) use ($errorHandler) {
            try {
                return $next($payload);
            } catch (Throwable $e) {
                return $errorHandler($e, $payload);
            }
        };

        return $this;
    }

    /**
     * Execute the workflow with the given payload.
     *
     * @param mixed $payload
     * @return mixed
     */
    public function execute(mixed $payload): mixed
    {
        $steps = $this->steps;

        if (null !== $this->logger) {
            $logger = $this->logger; // capture logger for closure.
            $steps = array_map(function (string|Closure $step) use ($logger): Closure {
                return function ($payload, Closure $next) use ($step, $logger) {
                    $stepName = is_string($step) ? $step : 'Closure';

                    $this->log(
                        message: "Before step: {$stepName}",
                        context: ['payload' => $payload],
                    );

                    if (is_callable($step)) {
                        $result = $step($payload, $next);
                    } else {
                        $resolved = $this->resolveStep($step);
                        $result = $resolved->handle($payload, $next);
                    }

                    $logger->info("After step: {$stepName}", ['result' => $result]);
                    return $result;
                };
            }, $steps);
        }

        return Pipeline::send($payload)
            ->through($steps)
            ->thenReturn();
    }

    /**
     * @param class-string<FlowStep> $step
     * @return FlowStep
     */
    private function resolveStep(string $step): FlowStep
    {
        try {
            $step = resolve($step);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                sprintf(
                    'Failed to resolve action class [%s]: %s',
                    $step,
                    $exception->getMessage(),
                ),
                0,
                $exception,
            );
        }

        return $step;
    }

    private function log(string $message, mixed $context): void
    {
        if (null === $this->logger) {
            throw new RuntimeException(
                message: 'You need to set a logger before running the flow using the debug method.',
            );
        }

        $this->logger->info(
            message: $message,
            context: (array) $context,
        );
    }
}

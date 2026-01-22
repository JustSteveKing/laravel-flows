<?php

declare(strict_types=1);

namespace JustSteveKing\Flows;

use Closure;
use Illuminate\Support\Facades\Pipeline;
use JustSteveKing\Flows\Contracts\FlowCondition;
use JustSteveKing\Flows\Contracts\FlowStep;
use JustSteveKing\Flows\Contracts\ValidatingStep;
use JustSteveKing\Flows\Steps\ValidationStep;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * @template TPayload
 */
final class Flow
{
    /**
     * Flow constructor.
     *
     * @param array<int, class-string<FlowStep>|Closure|FlowStep> $steps
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private array $steps = [],
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Create a new Flow instance.
     *
     * @return static<TPayload>
     */
    public static function start(): static
    {
        return new static();
    }

    /**
     * Set a logger for debugging.
     *
     * @param LoggerInterface $logger
     * @return static<TPayload>
     */
    public function debug(LoggerInterface $logger): static
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Add a step to the workflow.
     *
     * @param class-string<FlowStep>|Closure $action
     * @return static<TPayload>
     */
    public function run(string|Closure $action): static
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * @param class-string<ValidatingStep>|ValidatingStep $action
     * @return static<TPayload>
     */
    public function validate(string|ValidatingStep $action): static
    {
        if (is_string($action)) {
            $action = resolve($action);
        }

        $this->steps[] = new ValidationStep($action);

        return $this;
    }

    /**
     * Add a conditional branch to the workflow.
     *
     * @param class-string<FlowCondition> $condition
     * @param callable $callback
     * @return static<TPayload>
     */
    public function branch(string $condition, callable $callback): static
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
     * @return static<TPayload>
     */
    public function runIf(callable $condition, string $action): static
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
     * @return static<TPayload>
     */
    public function chain(string $action): static
    {
        $this->steps[] = $action;

        return $this;
    }

    /**
     * Add error handling to the workflow.
     *
     * @param callable $errorHandler
     * @return static<TPayload>
     */
    public function catch(callable $errorHandler): static
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
     * Add a reusable callback to the workflow.
     *
     * @param callable $callback
     * @return static<TPayload>
     */
    public function with(callable $callback): static
    {
        $callback($this);

        return $this;
    }

    /**
     * Execute the workflow with the given payload.
     *
     * @param TPayload $payload
     * @return TPayload
     */
    public function execute(mixed $payload): mixed
    {
        $steps = $this->steps;

        if (null !== $this->logger) {
            $logger = $this->logger; // capture logger for closure.
            $steps = array_map(function (string|Closure|FlowStep $step) use ($logger): Closure {
                return function ($payload, Closure $next) use ($step, $logger) {
                    $stepName = is_string($step) ? $step : get_class($step);

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
     * @param class-string<FlowStep>|FlowStep $step
     * @return FlowStep
     */
    private function resolveStep(string|FlowStep $step): FlowStep
    {
        if (is_object($step)) {
            return $step;
        }

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

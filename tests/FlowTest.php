<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests;

use Exception;
use JustSteveKing\Flows\Flow;
use JustSteveKing\Flows\Tests\Doubles\DummyCondition;
use JustSteveKing\Flows\Tests\Doubles\DummyStep;
use JustSteveKing\Flows\Tests\Doubles\ExceptionStep;
use JustSteveKing\Flows\Tests\Doubles\IsFalse;
use JustSteveKing\Flows\Tests\Doubles\IsTrue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;

final class FlowTest extends PackageTestCase
{
    #[Test]
    public function pipelineWithoutStepsReturnsPayload(): void
    {
        $payload = 'initial';
        $result = Flow::start()->execute($payload);
        $this->assertEquals('initial', $result);
    }

    #[Test]
    public function multipleStepsAreExecutedInOrder(): void
    {
        $flow = Flow::start()->run(
            action: DummyStep::class,
        )->run(
            action: DummyStep::class,
        );

        $result = $flow->execute('bar');
        $this->assertEquals('bar foo foo', $result);
    }

    #[Test]
    public function branchModifiesPayloadWhenConditionIsMet(): void
    {
        $flow = Flow::start()->branch(
            condition: DummyCondition::class,
            callback: fn(string $payload): string => $payload . ' branch',
        );

        $result = $flow->execute('run');
        $this->assertEquals('run branch', $result);
    }

    #[Test]
    public function branchDoesNotModifyPayloadWhenConditionIsNotMet(): void
    {
        $flow = Flow::start()->branch(
            condition: DummyCondition::class,
            callback: fn(string $payload): string => $payload . ' branch',
        );

        $result = $flow->execute('stop');
        $this->assertEquals('stop', $result);
    }

    #[Test]
    public function closureStepIsExecuted(): void
    {
        $flow = Flow::start()->run(static fn($payload, $next) => $next($payload . ' closure'));

        $result = $flow->execute('test');
        $this->assertEquals('test closure', $result);
    }

    #[Test]
    public function pipelineThrowsExceptionWhenStepFails(): void
    {
        $this->expectException(Exception::class);

        $flow = Flow::start()->run(
            action: ExceptionStep::class,
        );

        $flow->execute('fail');
    }

    #[Test]
    public function chainMethodAppendsFooOnce(): void
    {
        $flow = Flow::start()->chain(
            action: DummyStep::class,
        );
        $result = $flow->execute('hello');

        $this->assertEquals('hello foo', $result);
    }

    #[Test]
    public function branchExecutesCallbackWhenConditionIsMet(): void
    {
        $flow = Flow::start()->branch(
            condition: IsTrue::class,
            callback: fn(array $payload): array => array_merge($payload, ['modified' => true]),
        );

        $payload = ['data' => 'test'];
        $result = $flow->execute($payload);

        $this->assertTrue($result['modified'] ?? false);
    }

    #[Test]
    public function branchSkipsCallbackWhenConditionIsNotMet(): void
    {
        $flow = Flow::start()->branch(
            condition: IsFalse::class,
            callback: fn(array $payload): array => array_merge($payload, ['modified' => true]),
        );

        $payload = ['data' => 'test'];
        $result = $flow->execute($payload);

        $this->assertFalse(isset($result['modified']));
    }

    #[Test]
    public function runIfStepIsExecutedWhenConditionIsTrue(): void
    {
        $condition = fn($payload): bool => true;

        $flow = Flow::start()->runIf($condition, DummyStep::class);
        $result = $flow->execute('bar');

        $this->assertEquals('bar foo', $result);
    }

    #[Test]
    public function runIfStepIsSkippedWhenConditionIsFalse(): void
    {
        $condition = fn($payload): bool => false;

        $flow = Flow::start()->runIf($condition, DummyStep::class);
        $result = $flow->execute('bar');

        $this->assertEquals('bar', $result);
    }

    #[Test]
    public function runIfThrowsRuntimeExceptionWhenActionCannotBeResolved(): void
    {
        // Condition returns true, so the step will be attempted.
        $condition = fn($payload): bool => true;
        $invalidAction = 'NonExistent\Step';

        $flow = Flow::start()->runIf($condition, $invalidAction);

        $this->expectException(RuntimeException::class);
        $flow->execute('bar');
    }

    #[Test]
    public function catchHandlerInterceptsExceptions(): void
    {
        $this->expectException(Exception::class);

        $flow = Flow::start()
            ->run(ExceptionStep::class)
            ->catch(fn(Throwable $e, string $payload) => $payload . ' caught');

        $result = $flow->execute('test');

        $this->assertEquals('test caught', $result);
    }

    #[Test]
    public function catchHandlerIsSkippedWhenNoExceptionOccurs(): void
    {
        $flow = Flow::start()
            ->run(fn($payload, $next) => $next($payload . ' success'))
            ->catch(fn(Throwable $e, string $payload) => $payload . ' caught');

        $result = $flow->execute('test');

        $this->assertEquals('test success', $result);
    }

    #[Test]
    public function itCatchesSpecificExceptions(): void
    {
        $this->expectException(RuntimeException::class);
        $flow = Flow::start()
            ->run(function ($payload): void {
                throw new RuntimeException('Specific error');
            })
            ->catch(fn(RuntimeException $e, $payload) => 'caught runtime exception');

        $result = $flow->execute('test');

        $this->assertEquals('caught runtime exception', $result);
    }

    #[Test]
    public function itCanModifyPayloadInCatchBlock(): void
    {
        $this->expectException(Exception::class);
        $flow = Flow::start()
            ->run(function ($payload): void {
                throw new Exception('Failed operation');
            })
            ->catch(fn(Throwable $e, array $payload) => array_merge($payload, [
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]));

        $result = $flow->execute(['initial' => 'data']);

        $this->assertEquals([
            'initial' => 'data',
            'error' => 'Failed operation',
            'status' => 'failed',
        ], $result);
    }

    #[Test]
    public function itPropagatesUnhandledExceptions(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unhandled error');

        $flow = Flow::start()
            ->run(function ($payload): void {
                throw new RuntimeException('Unhandled error');
            });

        $flow->execute('test');
    }

    #[Test]
    public function itPreservesExceptionChain(): void
    {
        $this->expectException(RuntimeException::class);
        $previousException = new Exception('Previous error');
        $caught = null;

        $flow = Flow::start()
            ->run(function ($payload) use ($previousException): void {
                throw new RuntimeException('Main error', 0, $previousException);
            })
            ->catch(function (Throwable $e) use (&$caught) {
                $caught = $e;
                return 'handled';
            });

        $flow->execute('test');

        $this->assertInstanceOf(RuntimeException::class, $caught);
        $this->assertInstanceOf(Exception::class, $caught->getPrevious());
        $this->assertEquals('Previous error', $caught->getPrevious()->getMessage());
    }

    #[Test]
    public function it_propagates_exceptions_without_catch_handler(): void
    {
        $flow = Flow::start()
            ->run(function ($payload) {
                throw new RuntimeException('Unhandled error');
            });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unhandled error');

        $flow->execute('test');
    }
}

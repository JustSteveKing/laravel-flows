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
}

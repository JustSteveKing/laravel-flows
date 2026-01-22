<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests;

use Illuminate\Validation\ValidationException;
use JustSteveKing\Flows\Flow;
use JustSteveKing\Flows\Tests\Doubles\DummyValidatingStep;

class FlowValidationTest extends PackageTestCase
{
    /** @test */
    public function it_can_run_a_validation_step()
    {
        $result = Flow::start()
            ->validate(DummyValidatingStep::class)
            ->execute([
                'name' => 'Steve',
                'email' => 'steve@example.com',
            ]);

        $this->assertTrue($result['validated']);
    }

    /** @test */
    public function it_throws_a_validation_exception_if_the_data_is_invalid()
    {
        $this->expectException(ValidationException::class);

        Flow::start()
            ->validate(DummyValidatingStep::class)
            ->execute([
                'name' => 'Steve',
            ]);
    }
}

<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JustSteveKing\Flows\Flow;
use JustSteveKing\Flows\Tests\Doubles\CreateUserStep;
use JustSteveKing\Flows\Tests\Doubles\ExceptionStep;

class FlowTransactionTest extends PackageTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /** @test */
    public function it_can_run_a_flow_in_a_transaction()
    {
        $this->assertDatabaseCount('users', 0);

        Flow::start()
            ->run(CreateUserStep::class)
            ->transact([
                'name' => 'Steve',
                'email' => 'steve@example.com',
            ]);

        $this->assertDatabaseCount('users', 1);
    }

    /** @test */
    public function it_rolls_back_the_transaction_if_a_step_fails()
    {
        $this->assertDatabaseCount('users', 0);

        try {
            Flow::start()
                ->run(CreateUserStep::class)
                ->run(ExceptionStep::class)
                ->transact([
                    'name' => 'Steve',
                    'email' => 'steve@example.com',
                ]);
        } catch (\Exception) {
            // We expect an exception to be thrown
        }

        $this->assertDatabaseCount('users', 0);
    }
}

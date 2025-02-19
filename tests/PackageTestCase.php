<?php

declare(strict_types=1);

namespace JustSteveKing\Flows\Tests;

use JustSteveKing\Flows\Providers\PackageServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PackageServiceProvider::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Yeonik\LegacyPasswordUpgrader\LegacyPasswordUpgraderServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('legacy_password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [LegacyPasswordUpgraderServiceProvider::class];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        /** @var Repository $config */
        $config = $app->make(Repository::class);

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Point the default guard's provider at the legacy-eloquent driver.
        $config->set('auth.defaults.guard', 'web');
        $config->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $config->set('auth.providers.users', [
            'driver' => 'legacy-eloquent',
            'model' => TestUser::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

final class LegacyPasswordUpgraderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/legacy-password.php', 'legacy-password');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/legacy-password.php' => $this->app->configPath('legacy-password.php'),
        ], 'legacy-password-config');

        // Register the auth driver. Consumers point a provider at it in
        // config/auth.php: 'driver' => 'legacy-eloquent'.
        Auth::provider('legacy-eloquent', function (Application $app, array $config): LegacyUserProvider {
            $model = $config['model'] ?? null;

            if (! is_string($model) || $model === '') {
                throw new RuntimeException(
                    'The [legacy-eloquent] auth provider requires a [model] in its config/auth.php entry.'
                );
            }

            $settings = $app->make(Repository::class);

            $hasher = $app->make(Hasher::class);

            return new LegacyUserProvider(
                $hasher,
                $model,
                (bool) $settings->get('legacy-password.enabled', true),
                (string) $settings->get('legacy-password.column', 'legacy_password'),
                $this->resolveVerifiers($app, $settings->get('legacy-password.verifiers', [])),
            );
        });
    }

    /**
     * Instantiate the configured verifier classes through the container.
     *
     * @return list<LegacyHashVerifier>
     */
    private function resolveVerifiers(Application $app, mixed $classes): array
    {
        $verifiers = [];

        foreach ((array) $classes as $class) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            $instance = $app->make($class);

            if ($instance instanceof LegacyHashVerifier) {
                $verifiers[] = $instance;
            }
        }

        return $verifiers;
    }
}

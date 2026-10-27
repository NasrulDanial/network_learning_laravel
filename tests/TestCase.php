<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Safety net: never let feature tests write to a real development/production database.
        $database = (string) config('database.connections.' . config('database.default') . '.database');
        if (! str_ends_with($database, '_test')) {
            throw new \RuntimeException(
                "Refusing to run tests against database '{$database}'. Use a database ending in '_test' "
                . "(see phpunit.xml) and run 'php artisan config:clear' if config is cached."
            );
        }

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }
}

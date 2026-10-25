<?php

namespace Files13\Tests;

use App\Models\User;
use Basics13\Models\Role;
use Flux\FluxServiceProvider;
use Illuminate\Support\Facades\Blade;
use Basics13\Tests\TestCase as Basics13TestCase;
use Basics13\Support\Testing\ValidatesFormRequests;
use Files13\ServiceProvider as Files13ServiceProvider;
use Locations13\ServiceProvider as Locations13ServiceProvider;

abstract class TestCase extends Basics13TestCase
{
    use ValidatesFormRequests;

    /** @var class-string */
    protected string $seeder = AccessSeeder::class;

    /**
     * Loads the package so its config, translations and views are available to the tests.
     *
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FluxServiceProvider::class,
            Locations13ServiceProvider::class,
            Files13ServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // SQLite by default; CI sets DB_CONNECTION=mysql to run the tests that need MariaDB's row
        // locks, the same override the domain suites have.
        $app['config']->set('database.default', getenv('DB_CONNECTION') ?: 'sqlite');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('view.compiled', dirname(__DIR__).'/storage/framework/views');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Blade::anonymousComponentPath(__DIR__.'/Fixtures/views/layouts', 'layouts');
    }

    /**
     * Give the account the `admin` system role, which the access seeder grants full access to
     * every files13 module.
     */
    protected function admin(User $user): User
    {
        $user->assignRole(Role::query()->where('slug', 'admin')->firstOrFail());

        return $user;
    }

    /**
     * A verified account whose only role grants the given permissions.
     *
     * @param  list<string>  $permissions
     */
    protected function holding(array $permissions): User
    {
        $user = User::create([
            'name' => 'Limited user',
            'email' => 'limited-'.uniqid().'@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
        $user->assignRole(Role::factory()->withPermissions($permissions)->create());

        return $user;
    }
}

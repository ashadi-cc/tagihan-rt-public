<?php

namespace Tests\Feature\Api;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * Base class for API feature tests. The API is called by the WhatsApp bot,
 * which authenticates as an admin account holding the "admin:manage" ability.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected User $botUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = $this->createOrganization(['name' => 'RT 04']);
        $this->botUser = $this->createUser($this->organization, [
            'username' => 'admin',
            'blok' => 'ADMIN-0',
        ]);
    }

    /**
     * RefreshDatabase drops every table, so make sure we never point it at a
     * real database by accident (e.g. when the container's DB_DATABASE leaks in).
     */
    protected function beforeRefreshingDatabase()
    {
        $database = config('database.connections.' . config('database.default') . '.database');

        if (!str_contains($database, 'test')) {
            throw new RuntimeException("Refusing to run feature tests against non-test database [{$database}].");
        }
    }

    protected function actingAsBot(array $abilities = ['admin:manage']): User
    {
        Sanctum::actingAs($this->botUser, $abilities);

        return $this->botUser;
    }

    protected function createOrganization(array $attributes = []): Organization
    {
        return Organization::forceCreate(array_merge([
            'name' => 'RT ' . fake()->unique()->numberBetween(5, 99999),
            'description' => 'RT 04 / RW 01',
        ], $attributes));
    }

    protected function createUser(Organization $organization, array $attributes = []): User
    {
        static $sequence = 0;
        $sequence++;

        return User::factory()->create(array_merge([
            'organization_id' => $organization->id,
            'username' => 'warga' . $sequence,
            'blok' => 'A-' . $sequence,
            'default_password' => 'secret' . $sequence,
        ], $attributes));
    }
}

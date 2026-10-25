<?php

namespace Files13\Tests\Feature\FileLocations;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileLocationCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('file-locations.index'))->assertRedirect(route('login'));
        $this->post(route('file-locations.store'), ['name_eu' => 'Gaia'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('FIL_locations', 0);
    }

    public function test_users_without_the_location_permissions_receive_a_403(): void
    {
        $this->actingAs($this->user());

        $this->get(route('file-locations.index'))->assertForbidden();
        $this->post(route('file-locations.store'), ['name_eu' => 'Gaia'])->assertForbidden();
        $this->getJson(route('file-locations.options'))->assertForbidden();
        $this->assertDatabaseCount('FIL_locations', 0);
    }

    public function test_an_admin_creates_updates_and_trashes_a_location(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->from(route('file-locations.index'))
            ->post(route('file-locations.store'), ['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo'])
            ->assertRedirect(route('file-locations.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));
        $location = FileLocation::query()->sole();

        $this->get(route('file-locations.index'))->assertOk()
            ->assertSee('file-location-create')
            ->assertSee('Hirigintza')
            ->assertSee('Urbanismo');

        $this->put(route('file-locations.update', $location), ['name_eu' => 'Hirigintza berria'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Hirigintza berria', $location->refresh()->name_eu);

        $this->delete(route('file-locations.destroy', $location))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($location);
    }

    public function test_each_language_name_is_unique_among_the_locations_outside_the_trash(): void
    {
        $this->actingAs($this->admin($this->user()));
        FileLocation::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileLocation::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->post(route('file-locations.store'), ['name_eu' => 'Hirigintza'])->assertSessionHasErrors('name_eu');
        $this->post(route('file-locations.store'), ['name_eu' => 'Zaharra'])->assertSessionHasErrors('name_eu');
        $this->post(route('file-locations.store'), ['name_eu' => 'Beste bat', 'name_es' => 'Urbanismo'])->assertSessionHasErrors('name_es');
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->postJson(route('file-locations.store'), ['name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertDatabaseCount('FIL_locations', 1);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $location = FileLocation::factory()->create(['name_eu' => 'Gai originala']);
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->putJson(route('file-locations.update', $location), ['name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertSame('Gai originala', $location->refresh()->name_eu);
    }

    public function test_a_name_taken_by_a_trashed_location_opens_the_conflict(): void
    {
        $this->actingAs($this->admin($this->user()));
        $trashed = FileLocation::factory()->create(['name_eu' => 'Zaharra', 'name_es' => 'Viejo']);
        $trashed->delete();

        $this->post(route('file-locations.store'), ['name_eu' => 'Zaharra'])
            ->assertRedirect(route('file-locations.index'))
            ->assertSessionHas('deleted_file_location_conflict', fn (array $payload): bool => $payload['id'] === $trashed->id && $payload['name_es'] === 'Viejo');

        $this->postJson(route('file-locations.store'), ['name_eu' => 'Zaharra'])->assertConflict();

        $this->post(route('file-locations.store'), ['name_eu' => 'Zaharra', 'reuse_deleted_name' => true])
            ->assertSessionHas('status', __('basics13::messages.created'));
        $this->assertDatabaseCount('FIL_locations', 2);
    }

    public function test_store_answers_a_json_caller_with_the_created_location(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->postJson(route('file-locations.store'), ['name_eu' => 'Ingurumena'])
            ->assertCreated()
            ->assertJson(['name_eu' => 'Ingurumena']);
    }

    public function test_the_select_options_list_active_locations_matched_in_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        $urbanism = FileLocation::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileLocation::factory()->create(['name_eu' => 'Ingurumena']);
        FileLocation::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->getJson(route('file-locations.options'))->assertOk()->assertJsonCount(2, 'results');
        $this->getJson(route('file-locations.options', ['q' => 'urban']))
            ->assertExactJson(['results' => [['id' => $urbanism->id, 'text' => 'Hirigintza']]]);
    }

    public function test_the_list_search_matches_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        FileLocation::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileLocation::factory()->create(['name_eu' => 'Ingurumena', 'name_es' => 'Medio ambiente']);

        $this->get(route('file-locations.index', ['search' => 'urban']))->assertOk()
            ->assertSee('Hirigintza')
            ->assertDontSee('Ingurumena');
    }

    /**
     * A competitor takes the name between the unique rule and the insert, the window the database
     * index is the only thing closing.
     */
    private function createDuplicateNameAfterValidation(string $name): void
    {
        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use ($name, &$competitorCreated): void {
            if (
                $competitorCreated
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')
                || ! str_contains(strtolower($query->sql), 'fil_locations')
                || ! in_array($name, $query->bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            FileLocation::factory()->create(['name_eu' => $name]);
        });
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Test user',
            'email' => 'test-'.uniqid().'@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
    }
}

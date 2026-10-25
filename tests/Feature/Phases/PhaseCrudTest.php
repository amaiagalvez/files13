<?php

namespace Files13\Tests\Feature\Phases;

use App\Models\User;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class PhaseCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('phases.index'))->assertRedirect(route('login'));
        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Gaia'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('FIL_phases', 0);
    }

    public function test_users_without_the_phase_permissions_receive_a_403(): void
    {
        $this->actingAs($this->user());

        $this->get(route('phases.index'))->assertForbidden();
        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Gaia'])->assertForbidden();
        $this->getJson(route('phases.options'))->assertForbidden();
        $this->assertDatabaseCount('FIL_phases', 0);
    }

    public function test_an_admin_creates_updates_and_trashes_a_phase(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->from(route('phases.index'))
            ->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo'])
            ->assertRedirect(route('phases.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));
        $phase = Phase::query()->sole();

        $this->get(route('phases.index'))->assertOk()
            ->assertSee('phase-create')
            ->assertSee('Hirigintza')
            ->assertSee('Urbanismo');

        $this->put(route('phases.update', $phase), ['order' => 1, 'name_eu' => 'Hirigintza berria'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Hirigintza berria', $phase->refresh()->name_eu);

        $this->delete(route('phases.destroy', $phase))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($phase);
    }

    public function test_each_language_name_is_unique_among_the_phases_outside_the_trash(): void
    {
        $this->actingAs($this->admin($this->user()));
        Phase::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        Phase::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Hirigintza'])->assertSessionHasErrors('name_eu');
        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Zaharra'])->assertSessionHasErrors('name_eu');
        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Beste bat', 'name_es' => 'Urbanismo'])->assertSessionHasErrors('name_es');
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->postJson(route('phases.store'), ['order' => 1, 'name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertDatabaseCount('FIL_phases', 1);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $phase = Phase::factory()->create(['name_eu' => 'Gai originala']);
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->putJson(route('phases.update', $phase), ['order' => 1, 'name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertSame('Gai originala', $phase->refresh()->name_eu);
    }

    public function test_a_name_taken_by_a_trashed_phase_opens_the_conflict(): void
    {
        $this->actingAs($this->admin($this->user()));
        $trashed = Phase::factory()->create(['name_eu' => 'Zaharra', 'name_es' => 'Viejo']);
        $trashed->delete();

        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Zaharra'])
            ->assertRedirect(route('phases.index'))
            ->assertSessionHas('deleted_phase_conflict', fn (array $payload): bool => $payload['id'] === $trashed->id && $payload['name_es'] === 'Viejo');

        $this->postJson(route('phases.store'), ['order' => 1, 'name_eu' => 'Zaharra'])->assertConflict();

        $this->post(route('phases.store'), ['order' => 1, 'name_eu' => 'Zaharra', 'reuse_deleted_name' => true])
            ->assertSessionHas('status', __('basics13::messages.created'));
        $this->assertDatabaseCount('FIL_phases', 2);
    }

    public function test_store_answers_a_json_caller_with_the_created_phase(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->postJson(route('phases.store'), ['order' => 1, 'name_eu' => 'Ingurumena'])
            ->assertCreated()
            ->assertJson(['name_eu' => 'Ingurumena']);
    }

    public function test_the_select_options_list_active_phases_matched_in_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        $urbanism = Phase::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        Phase::factory()->create(['name_eu' => 'Ingurumena']);
        Phase::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->getJson(route('phases.options'))->assertOk()->assertJsonCount(2, 'results');
        $this->getJson(route('phases.options', ['q' => 'urban']))
            ->assertExactJson(['results' => [['id' => $urbanism->id, 'text' => 'Hirigintza']]]);
    }

    public function test_the_list_search_matches_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        Phase::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        Phase::factory()->create(['name_eu' => 'Ingurumena', 'name_es' => 'Medio ambiente']);

        $this->get(route('phases.index', ['search' => 'urban']))->assertOk()
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
                || ! str_contains(strtolower($query->sql), 'fil_phases')
                || ! in_array($name, $query->bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            Phase::factory()->create(['name_eu' => $name]);
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

<?php

namespace Files13\Tests\Feature\FileTypes;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileTypeCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('file-types.index'))->assertRedirect(route('login'));
        $this->post(route('file-types.store'), ['name_eu' => 'Gaia'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('FIL_types', 0);
    }

    public function test_users_without_the_type_permissions_receive_a_403(): void
    {
        $this->actingAs($this->user());

        $this->get(route('file-types.index'))->assertForbidden();
        $this->post(route('file-types.store'), ['name_eu' => 'Gaia'])->assertForbidden();
        $this->getJson(route('file-types.options'))->assertForbidden();
        $this->assertDatabaseCount('FIL_types', 0);
    }

    public function test_an_admin_creates_updates_and_trashes_a_type(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->from(route('file-types.index'))
            ->post(route('file-types.store'), ['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo'])
            ->assertRedirect(route('file-types.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));
        $type = FileType::query()->sole();

        $this->get(route('file-types.index'))->assertOk()
            ->assertSee('file-type-create')
            ->assertSee('Hirigintza')
            ->assertSee('Urbanismo');

        $this->put(route('file-types.update', $type), ['name_eu' => 'Hirigintza berria'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Hirigintza berria', $type->refresh()->name_eu);

        $this->delete(route('file-types.destroy', $type))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($type);
    }

    public function test_each_language_name_is_unique_among_the_types_outside_the_trash(): void
    {
        $this->actingAs($this->admin($this->user()));
        FileType::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileType::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->post(route('file-types.store'), ['name_eu' => 'Hirigintza'])->assertSessionHasErrors('name_eu');
        $this->post(route('file-types.store'), ['name_eu' => 'Zaharra'])->assertSessionHasErrors('name_eu');
        $this->post(route('file-types.store'), ['name_eu' => 'Beste bat', 'name_es' => 'Urbanismo'])->assertSessionHasErrors('name_es');
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->postJson(route('file-types.store'), ['name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertDatabaseCount('FIL_types', 1);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->admin($this->user()));
        $type = FileType::factory()->create(['name_eu' => 'Gai originala']);
        $this->createDuplicateNameAfterValidation('Gai konkurrentea');

        $this->putJson(route('file-types.update', $type), ['name_eu' => 'Gai konkurrentea'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');
        $this->assertSame('Gai originala', $type->refresh()->name_eu);
    }

    public function test_a_name_taken_by_a_trashed_type_opens_the_conflict(): void
    {
        $this->actingAs($this->admin($this->user()));
        $trashed = FileType::factory()->create(['name_eu' => 'Zaharra', 'name_es' => 'Viejo']);
        $trashed->delete();

        $this->post(route('file-types.store'), ['name_eu' => 'Zaharra'])
            ->assertRedirect(route('file-types.index'))
            ->assertSessionHas('deleted_file_type_conflict', fn (array $payload): bool => $payload['id'] === $trashed->id && $payload['name_es'] === 'Viejo');

        $this->postJson(route('file-types.store'), ['name_eu' => 'Zaharra'])->assertConflict();

        $this->post(route('file-types.store'), ['name_eu' => 'Zaharra', 'reuse_deleted_name' => true])
            ->assertSessionHas('status', __('basics13::messages.created'));
        $this->assertDatabaseCount('FIL_types', 2);
    }

    public function test_store_answers_a_json_caller_with_the_created_type(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->postJson(route('file-types.store'), ['name_eu' => 'Ingurumena'])
            ->assertCreated()
            ->assertJson(['name_eu' => 'Ingurumena']);
    }

    public function test_the_select_options_list_active_types_matched_in_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        $urbanism = FileType::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileType::factory()->create(['name_eu' => 'Ingurumena']);
        FileType::factory()->archived()->create(['name_eu' => 'Zaharra']);

        $this->getJson(route('file-types.options'))->assertOk()->assertJsonCount(2, 'results');
        $this->getJson(route('file-types.options', ['q' => 'urban']))
            ->assertExactJson(['results' => [['id' => $urbanism->id, 'text' => 'Hirigintza']]]);
    }

    public function test_the_list_search_matches_any_language(): void
    {
        $this->actingAs($this->admin($this->user()));
        FileType::factory()->create(['name_eu' => 'Hirigintza', 'name_es' => 'Urbanismo']);
        FileType::factory()->create(['name_eu' => 'Ingurumena', 'name_es' => 'Medio ambiente']);

        $this->get(route('file-types.index', ['search' => 'urban']))->assertOk()
            ->assertSee('Hirigintza')
            ->assertDontSee('Ingurumena');
    }

    public function test_the_company_and_person_flags_are_saved_and_cleared(): void
    {
        $this->actingAs($this->admin($this->user()));

        $this->post(route('file-types.store'), ['name_eu' => 'Lizentzia', 'company' => '1', 'person' => '1'])
            ->assertSessionHasNoErrors();
        $type = FileType::query()->sole();
        $this->assertTrue($type->company);
        $this->assertTrue($type->person);

        // An unticked checkbox sends nothing: the flag goes back to false.
        $this->put(route('file-types.update', $type), ['name_eu' => 'Lizentzia', 'company' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue($type->refresh()->company);
        $this->assertFalse($type->person);
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
                || ! str_contains(strtolower($query->sql), 'fil_types')
                || ! in_array($name, $query->bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            FileType::factory()->create(['name_eu' => $name]);
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

<?php

namespace Files13\Tests\Feature\Files;

use App\Models\User;
use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Locations13\Models\District;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileCrudTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Relation::morphMap([], false);
        Relation::$morphMap = [];

        parent::tearDown();
    }

    public function test_guests_are_redirected_and_users_without_permissions_are_refused(): void
    {
        $this->get(route('files.index'))->assertRedirect(route('login'));

        $this->actingAs($this->holding([]));
        $this->get(route('files.index'))->assertForbidden();
        $this->post(route('files.store'), ['name' => 'Espedientea'])->assertForbidden();
    }

    public function test_a_file_is_created_shown_updated_and_trashed(): void
    {
        $this->actingAs($manager = $this->holding(['files.manage']));
        $type = FileType::factory()->create();

        $this->post(route('files.store'), ['name' => '2026/001 Lizentzia', 'type_id' => $type->id, 'user_ids' => [$manager->id]])
            ->assertSessionHasNoErrors();
        $file = File::query()->sole();
        $this->assertSame([$manager->id], $file->users->pluck('id')->all());

        $this->get(route('files.index'))->assertOk()->assertSee('2026/001 Lizentzia');
        $this->get(route('files.show', $file))->assertOk()->assertSee('2026/001 Lizentzia')->assertSee('file-phases-form', false);

        $this->put(route('files.update', $file), ['name' => '2026/001 Lizentzia berria', 'type_id' => $type->id, 'user_ids' => ''])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026/001 Lizentzia berria', $file->refresh()->name);
        $this->assertCount(0, $file->users);

        $this->delete(route('files.destroy', $file))->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($file);
    }

    public function test_a_file_with_a_type_gets_the_active_phases_of_the_type_in_order(): void
    {
        $this->actingAs($manager = $this->holding(['files.manage']));
        $type = FileType::factory()->create();
        $second = Phase::factory()->create(['order' => 2]);
        $first = Phase::factory()->create(['order' => 1]);
        $archived = Phase::factory()->archived()->create(['order' => 3]);
        $other = Phase::factory()->create(['order' => 4]);
        foreach ([$second, $first, $archived] as $phase) {
            $phase->types()->attach($type);
        }

        $this->post(route('files.store'), ['name' => 'Espedientea', 'type_id' => $type->id])->assertSessionHasNoErrors();

        $file = File::query()->sole();
        $this->assertSame([$first->id, $second->id], $file->filePhases->pluck('phase_id')->all());
        $this->assertSame([$manager->id, $manager->id], $file->filePhases->pluck('user_id')->all());
        $this->assertNotContains($other->id, $file->filePhases->pluck('phase_id')->all());
    }

    public function test_a_file_may_have_no_district(): void
    {
        $this->actingAs($this->holding(['files.manage']));

        $this->post(route('files.store'), ['name' => 'Barrutirik gabe', 'type_id' => FileType::factory()->create()->id, 'district_id' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull(File::query()->sole()->district_id);
    }

    public function test_an_archived_district_stays_when_assigned_and_is_refused_otherwise(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $district = District::factory()->create();
        $file = File::factory()->create(['district_id' => $district->id]);
        $district->forceFill(['active' => false])->save();
        $other = District::factory()->create(['active' => false]);

        $this->put(route('files.update', $file), ['name' => $file->name, 'type_id' => $file->type_id, 'district_id' => $district->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($district->id, $file->refresh()->district_id);

        $this->put(route('files.update', $file), ['name' => $file->name, 'type_id' => $file->type_id, 'district_id' => $other->id])
            ->assertSessionHasErrors('district_id');
    }

    public function test_the_key_refuses_deleting_a_district_a_file_names(): void
    {
        $district = District::factory()->create();
        File::factory()->create(['district_id' => $district->id]);

        $this->expectException(QueryException::class);
        DB::table('LOC_districts')->where('id', $district->id)->delete();
    }

    public function test_applicants_need_a_registered_kind_and_appear_once(): void
    {
        Relation::morphMap(['tester' => User::class]);
        $this->actingAs($manager = $this->holding(['files.manage']));
        $type = FileType::factory()->create();

        $this->post(route('files.store'), ['name' => 'Eskatzailea', 'type_id' => $type->id, 'applicants' => [['applicant_type' => 'App\\Models\\Post', 'applicant_id' => 1]]])
            ->assertSessionHasErrors('applicants.0.applicant_type');

        $this->post(route('files.store'), ['name' => 'Eskatzailea', 'type_id' => $type->id, 'applicants' => [
            ['applicant_type' => 'tester', 'applicant_id' => $manager->id],
            ['applicant_type' => 'tester', 'applicant_id' => $manager->id],
        ]])->assertSessionHasErrors('applicants.1.applicant_id');

        $this->post(route('files.store'), ['name' => 'Eskatzailea', 'type_id' => $type->id, 'applicants' => [['applicant_type' => 'tester', 'applicant_id' => $manager->id]]])
            ->assertSessionHasNoErrors();
        $this->assertSame('tester', File::query()->sole()->applicants->sole()->applicant_type);
    }

    public function test_an_applicant_whose_record_is_gone_does_not_break_the_sheet(): void
    {
        Relation::morphMap(['tester' => User::class]);
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();
        $file->applicants()->create(['applicant_type' => 'tester', 'applicant_id' => 999999]);

        $this->get(route('files.show', $file))->assertOk()->assertDontSee('#999999');
    }

    public function test_a_user_responsible_for_a_phase_is_not_removed_from_the_file(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $responsible = $this->holding([]);
        $file = File::factory()->create();
        $file->users()->attach($responsible);
        $file->filePhases()->create(['phase_id' => Phase::factory()->create()->id, 'user_id' => $responsible->id]);

        $this->put(route('files.update', $file), ['name' => $file->name, 'type_id' => $file->type_id, 'user_ids' => ''])
            ->assertSessionHasErrors('user_ids');
        $this->assertCount(1, $file->refresh()->users);
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $type = FileType::factory()->create();
        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use (&$competitorCreated, $type): void {
            if ($competitorCreated || ! str_starts_with(strtolower(ltrim($query->sql)), 'select') || ! in_array('Lasterketa', $query->bindings, true)) {
                return;
            }

            $competitorCreated = true;
            File::factory()->create(['name' => 'Lasterketa', 'type_id' => $type->id]);
        });

        $this->postJson(route('files.store'), ['name' => 'Lasterketa', 'type_id' => $type->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_the_trash_restores_and_deletes_for_good(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->trashed()->create(['name' => 'Zakarrontzian']);

        $this->get(route('files.trash.index'))->assertOk()->assertSee('Zakarrontzian');
        $this->patch(route('files.trash.restore', $file->id))->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($file);

        $file->delete();
        $this->delete(route('files.trash.destroy', $file->id))->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_files', ['id' => $file->id]);
    }

    public function test_a_file_is_archived_and_activated(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create(['name' => 'Artxibagarria']);

        $this->patch(route('files.archive', $file))->assertSessionHas('status', __('basics13::messages.archived'));
        $this->get(route('files.archived.index'))->assertOk()->assertSee('Artxibagarria');
        $this->patch(route('files.archived.activate', $file))->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($file->refresh()->active);
    }
}

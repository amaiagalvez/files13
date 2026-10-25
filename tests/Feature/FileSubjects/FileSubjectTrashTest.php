<?php

namespace Files13\Tests\Feature\FileSubjects;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileSubject;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileSubjectTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_subjects_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        FileSubject::factory()->create(['name_eu' => 'Bizirik']);
        FileSubject::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('file-subjects.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('file-subjects.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_subject_is_restored(): void
    {
        $this->actingAs($this->manager());
        $subject = FileSubject::factory()->trashed()->create();

        $this->patch(route('file-subjects.trash.restore', $subject->id))
            ->assertRedirect(route('file-subjects.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($subject);
    }

    public function test_a_subject_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $subject = FileSubject::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        FileSubject::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('file-subjects.trash.restore', $subject->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($subject);
    }

    public function test_a_trashed_subject_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $subject = FileSubject::factory()->trashed()->create();

        $this->delete(route('file-subjects.trash.destroy', $subject->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_subjects', ['id' => $subject->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $subject = FileSubject::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_subjects.view', 'file_subjects.delete']));

        $this->get(route('file-subjects.trash.index'))->assertOk();
        $this->patch(route('file-subjects.trash.restore', $subject->id))->assertForbidden();
        $this->delete(route('file-subjects.trash.destroy', $subject->id))->assertForbidden();
        $this->assertSoftDeleted($subject);
    }

    private function manager(): User
    {
        return $this->holding(['file_subjects.manage']);
    }
}

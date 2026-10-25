<?php

namespace Files13\Tests\Feature\FileSubjects;

use Files13\Tests\TestCase;
use Files13\Models\FileSubject;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileSubjectArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_subject_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_subjects.manage']));
        $subject = FileSubject::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('file-subjects.archive', $subject))
            ->assertRedirect(route('file-subjects.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($subject->refresh()->active);
        $this->get(route('file-subjects.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('file-subjects.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('file-subjects.archived.activate', $subject))
            ->assertRedirect(route('file-subjects.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($subject->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_subjects.view', 'file_subjects.edit']));
        $subject = FileSubject::factory()->create();

        $this->patch(route('file-subjects.archive', $subject))->assertForbidden();
        $this->assertTrue($subject->refresh()->active);
    }
}

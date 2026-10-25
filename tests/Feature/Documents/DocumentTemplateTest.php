<?php

namespace Files13\Tests\Feature\Documents;

use Files13\Tests\TestCase;
use Files13\Models\Document;
use Files13\Models\FileSubject;
use Files13\Models\DocumentTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DocumentTemplateTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_a_template_is_created_with_its_content_and_subjects_and_updated(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));
        $subject = FileSubject::factory()->create();

        $this->post(route('documents.store'), [
            'name' => 'Lizentzia eskaera',
            'content' => UploadedFile::fake()->create('eskaera.docx', 10),
            'subject_ids' => [$subject->id],
        ])->assertSessionHasNoErrors();

        $template = Document::query()->sole();
        $this->assertTrue($template->template);
        $this->assertSame('docx', $template->extension);
        $this->assertNotSame('eskaera.docx', basename((string) $template->path));
        Storage::disk('local')->assertExists((string) $template->path);
        $this->assertSame([$subject->id], $template->subjects->pluck('id')->all());
        $this->assertSame(['create'], $template->tracks->pluck('action')->all());

        $this->get(route('documents.index'))->assertOk()->assertSee('Lizentzia eskaera');

        $this->put(route('documents.update', $template), ['name' => 'Lizentzia eskaera berria', 'subject_ids' => []])->assertSessionHasNoErrors();
        $this->assertSame('Lizentzia eskaera berria', $template->refresh()->name);
        $this->assertCount(0, $template->subjects);
    }

    public function test_a_file_type_onlyoffice_does_not_edit_is_refused(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));

        $this->post(route('documents.store'), ['name' => 'Orria', 'content' => UploadedFile::fake()->create('orria.html', 1)])
            ->assertSessionHasErrors('content');
        $this->post(route('documents.store'), ['name' => 'Programa', 'content' => UploadedFile::fake()->create('programa.php', 1)])
            ->assertSessionHasErrors('content');
        $this->assertDatabaseCount('FIL_documents', 0);
    }

    public function test_a_template_open_in_the_editor_is_not_deleted(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));
        $template = Document::factory()->template()->create();
        $template->forceFill(['editing' => true])->save();

        $this->delete(route('documents.destroy', $template))->assertSessionHas('error', __('A document open in the editor cannot be deleted.'));
        $this->assertNotSoftDeleted($template);
    }

    public function test_a_template_with_copies_is_not_deleted_for_good_and_one_without_is(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));
        $used = Document::factory()->template()->create();
        $used->copyFromTemplate()->save();
        $unused = Document::factory()->template()->create();
        $used->delete();
        $unused->delete();

        $this->delete(route('documents.trash.destroy', $used->id))->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));
        $this->assertDatabaseHas('FIL_documents', ['id' => $used->id]);

        $this->delete(route('documents.trash.destroy', $unused->id))->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_documents', ['id' => $unused->id]);
        $this->assertSame(['destroy', 'delete'], DocumentTrack::query()->where('document_name', $unused->name)->latest('id')->pluck('action')->all());
    }

    public function test_trash_and_restore_are_tracked_and_archiving_works(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));
        $template = Document::factory()->template()->create();

        $this->delete(route('documents.destroy', $template));
        $this->patch(route('documents.trash.restore', $template->id))->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertSame(['restore', 'delete'], $template->tracks()->pluck('action')->all());

        $this->patch(route('documents.archive', $template))->assertSessionHas('status', __('basics13::messages.archived'));
        $this->get(route('documents.archived.index'))->assertOk()->assertSee($template->name);
    }

    public function test_the_documents_of_files_are_not_listed_as_templates(): void
    {
        $this->actingAs($this->holding(['file_documents.manage']));
        Document::factory()->create(['name' => 'Espedientearen dokumentua']);

        $this->get(route('documents.index'))->assertOk()->assertDontSee('Espedientearen dokumentua');
    }
}

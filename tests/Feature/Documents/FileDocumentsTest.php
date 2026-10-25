<?php

namespace Files13\Tests\Feature\Documents;

use Files13\Models\File;
use Files13\Models\Folder;
use Files13\Tests\TestCase;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileDocumentsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_a_document_copied_from_a_template_points_at_it_and_copies_the_content(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $template = Document::factory()->template()->create();
        $template->storeUpload(UploadedFile::fake()->create('txantiloia.odt', 5));
        $template->save();
        $file = File::factory()->create();

        $this->post(route('files.documents.store', $file), ['template_id' => $template->id, 'folder' => 'Eskaerak'])
            ->assertSessionHasNoErrors();

        $copy = $file->documents()->sole();
        $this->assertSame($template->id, $copy->parent_id);
        $this->assertSame($template->name, $copy->name);
        $this->assertFalse($copy->template);
        $this->assertNotSame($template->path, $copy->path);
        Storage::disk('local')->assertExists((string) $copy->path);
        $this->get(route('files.show', $file))->assertOk()->assertSee('Eskaerak')->assertSee($copy->name);
    }

    public function test_a_document_without_a_template_is_blank_or_uploaded(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();

        $this->post(route('files.documents.store', $file), ['name' => 'Txostena'])->assertSessionHasNoErrors();
        $this->post(route('files.documents.store', $file), ['name' => 'Argazkia', 'content' => UploadedFile::fake()->create('argazkia.exe', 1)])
            ->assertSessionHasErrors('content');
        $this->post(route('files.documents.store', $file), [])->assertSessionHasErrors('name');

        $this->assertNull($file->documents()->sole()->path);
    }

    public function test_folders_are_shared_and_reused_by_name(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        [$first, $second] = File::factory()->count(2)->create();

        $this->post(route('files.documents.store', $first), ['name' => 'Bat', 'folder' => 'Fakturak']);
        $this->post(route('files.documents.store', $second), ['name' => 'Bi', 'folder' => 'Fakturak']);
        $this->post(route('files.documents.store', $second), ['name' => 'Hiru', 'folder' => '']);

        $this->assertSame(1, Folder::query()->count());
        $document = $second->documents()->where('name', 'Bi')->sole();
        $this->patch(route('files.documents.update', [$second, $document]), ['folder' => ''])->assertSessionHasNoErrors();
        $this->assertNull($second->documents()->whereKey($document->id)->sole()->getRelation('pivot')->getAttribute('folder_id'));
        $this->assertSame(1, Folder::query()->count());
    }

    public function test_a_document_of_another_file_is_refused(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        [$file, $other] = File::factory()->count(2)->create();
        $foreign = Document::factory()->create();
        $other->documents()->attach($foreign);

        $this->patch(route('files.documents.update', [$file, $foreign]), ['folder' => 'Lapurra'])->assertForbidden();
        $this->delete(route('files.documents.destroy', [$file, $foreign]))->assertForbidden();
        $this->assertNotSoftDeleted($foreign);
        $this->assertSame(0, Folder::query()->count());
    }

    public function test_a_document_open_in_the_editor_is_not_removed(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();
        $document = Document::factory()->create();
        $document->forceFill(['editing' => true])->save();
        $file->documents()->attach($document);

        $this->delete(route('files.documents.destroy', [$file, $document]))
            ->assertSessionHas('error', __('A document open in the editor cannot be deleted.'));
        $this->assertNotSoftDeleted($document);
    }

    public function test_a_user_limited_to_own_files_cannot_touch_the_documents_of_another(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $foreign = File::factory()->create();

        $this->actingAs($this->holding(['files.view', 'files.manage_own']));
        $this->post(route('files.documents.store', $foreign), ['name' => 'Arrotza'])->assertForbidden();
        $this->get(route('files.show', $foreign))->assertOk()->assertDontSee('file-document-form', false);
    }

    public function test_deleting_a_file_for_good_deletes_its_documents_and_their_content_and_keeps_the_tracks(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();
        $document = Document::factory()->create(['name' => 'Ezabatzekoa']);
        $document->storeUpload(UploadedFile::fake()->create('ezabatzekoa.docx', 3));
        $document->save();
        $file->documents()->attach($document);
        $document->delete();
        $path = (string) $document->path;
        $file->delete();

        $this->delete(route('files.trash.destroy', $file->id))->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('FIL_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($path);
        $this->assertTrue(DocumentTrack::query()->where('document_name', 'Ezabatzekoa')->where('action', 'destroy')->exists());
    }
}

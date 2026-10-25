<?php

namespace Files13\Tests\Feature\OnlyOffice;

use App\Models\User;
use Files13\Models\File;
use Files13\Tests\TestCase;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Http\UploadedFile;
use Files13\Support\OnlyOfficeToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class OnlyOfficeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const SECRET = 'test-secret';

    private const SERVER_IP = '10.0.0.5';

    private Document $document;

    private File $file;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config([
            'files13.onlyoffice.server' => 'https://office.example.test',
            'files13.onlyoffice.ips' => [self::SERVER_IP],
            'files13.onlyoffice.jwt_secret' => self::SECRET,
        ]);

        $this->editor = $this->holding(['files.manage']);
        $this->file = File::factory()->create();
        $this->document = Document::factory()->create(['name' => 'Txostena']);
        $this->document->storeUpload(UploadedFile::fake()->createWithContent('txostena.docx', 'zaharra'));
        $this->document->save();
        $this->file->documents()->attach($this->document);
    }

    public function test_an_unsigned_or_wrongly_signed_callback_is_refused_and_changes_nothing(): void
    {
        $key = $this->document->key;

        $this->fromServer()->postJson(route('onlyoffice.callback', $this->document), ['status' => 2, 'url' => 'https://office.example.test/save'])->assertForbidden();
        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => OnlyOfficeToken::encode(['document' => $this->document->id, 'readonly' => false], 'beste-bat')]), ['status' => 2])->assertForbidden();

        $this->assertSame($key, $this->document->refresh()->key);
        $this->assertSame(0, DocumentTrack::query()->where('action', 'write')->count());
    }

    public function test_an_untrusted_address_is_refused_on_both_endpoints(): void
    {
        $token = $this->token(readonly: false);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $token]))->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $token]), ['status' => 4])->assertForbidden();
    }

    public function test_the_download_needs_the_token_of_that_document(): void
    {
        $other = Document::factory()->create();

        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $this->token(readonly: true)]))->assertOk();
        $this->fromServer()->get(route('onlyoffice.get', ['document' => $other, 'token' => $this->token(readonly: true)]))->assertForbidden();
    }

    public function test_a_token_without_a_user_is_refused(): void
    {
        $token = OnlyOfficeToken::encode(['document' => $this->document->id, 'readonly' => true, 'user' => null], self::SECRET);

        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $token]))->assertForbidden();
    }

    public function test_a_token_of_an_inactive_user_is_refused(): void
    {
        $token = $this->token(readonly: true);
        $this->editor->forceFill(['active' => false])->save();

        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $token]))->assertForbidden();
    }

    public function test_a_token_of_an_archived_document_is_refused(): void
    {
        $token = $this->token(readonly: true);
        $this->document->forceFill(['active' => false])->save();

        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $token]))->assertForbidden();
    }

    public function test_an_editable_token_of_a_user_who_lost_the_edit_permission_is_refused(): void
    {
        Http::fake();
        $viewer = $this->holding(['files.view']);
        $editable = $this->token(readonly: false, user: $viewer);

        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $editable]))->assertForbidden();
        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $editable]), [
            'status' => 2,
            'url' => 'https://office.example.test/save',
        ])->assertForbidden();

        Http::assertNothingSent();
        $this->fromServer()->get(route('onlyoffice.get', ['document' => $this->document, 'token' => $this->token(readonly: true, user: $viewer)]))->assertOk();
    }

    public function test_a_valid_save_writes_the_content_and_a_track(): void
    {
        Http::fake(['office.example.test/*' => Http::response('berria')]);
        $key = $this->document->key;

        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $this->token(readonly: false)]), [
            'status' => 2,
            'url' => 'https://office.example.test/cache/files/save.docx',
        ])->assertOk()->assertJson(['error' => 0]);

        $this->document->refresh();
        $this->assertSame('berria', Storage::disk('local')->get((string) $this->document->path));
        $this->assertNotSame($key, $this->document->key);
        $this->assertFalse($this->document->editing);
        $this->assertTrue(DocumentTrack::query()->where('document_id', $this->document->id)->where('action', 'write')->exists());
    }

    public function test_content_from_another_host_is_refused(): void
    {
        Http::fake();

        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $this->token(readonly: false)]), [
            'status' => 2,
            'url' => 'http://169.254.169.254/latest/meta-data',
        ])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame('zaharra', Storage::disk('local')->get((string) $this->document->refresh()->path));
    }

    public function test_a_read_only_session_never_saves(): void
    {
        Http::fake();

        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $this->token(readonly: true)]), [
            'status' => 2,
            'url' => 'https://office.example.test/save',
        ])->assertOk();

        Http::assertNothingSent();
        $this->assertSame('zaharra', Storage::disk('local')->get((string) $this->document->refresh()->path));
    }

    public function test_the_session_marks_the_document_as_open_and_closed(): void
    {
        $token = $this->token(readonly: false);

        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $token]), ['status' => 1])->assertOk();
        $this->assertTrue($this->document->refresh()->editing);

        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $token]), ['status' => 4])->assertOk();
        $this->assertFalse($this->document->refresh()->editing);
    }

    public function test_the_editor_page_follows_the_permissions_of_the_file(): void
    {
        $this->get(route('documents.editor', $this->document))->assertRedirect(route('login'));

        $this->actingAs($this->holding([]));
        $this->get(route('documents.editor', $this->document))->assertForbidden();

        $this->actingAs($this->holding(['files.view']));
        $this->get(route('documents.editor', $this->document))->assertOk()
            ->assertSee(__('You can read this document but not change it.'))
            ->assertSee('office.example.test/web-apps/apps/api/documents/api.js', false);

        $this->actingAs($this->holding(['files.manage']));
        $this->get(route('documents.editor', $this->document))->assertOk()
            ->assertDontSee(__('You can read this document but not change it.'));
    }

    public function test_without_a_secret_the_editor_says_so_and_the_callback_refuses(): void
    {
        $token = $this->token(readonly: false);
        config(['files13.onlyoffice.jwt_secret' => null]);

        $this->actingAs($this->holding(['files.manage']));
        $this->get(route('documents.editor', $this->document))->assertOk()->assertSee(__('The document editor is not configured.'));
        $this->fromServer()->postJson(route('onlyoffice.callback', ['document' => $this->document, 'token' => $token]), ['status' => 4])->assertForbidden();
    }

    private function token(bool $readonly, ?User $user = null): string
    {
        return OnlyOfficeToken::encode(['document' => $this->document->id, 'readonly' => $readonly, 'user' => ($user ?? $this->editor)->id], self::SECRET);
    }

    private function fromServer(): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => self::SERVER_IP]);
    }
}

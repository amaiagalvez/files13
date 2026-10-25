<?php

namespace Files13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Files13\Models\FileSubject;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Files13\Transformers\FileSubjectListTransformer;

final class FileSubjectListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/subjects/'.$name, fn () => null)->name('file-subjects.'.$name);
        }

        Route::delete('files/subjects/{record}', fn () => null)->name('file-subjects.destroy');
        Route::patch('files/subjects/{record}/archive', fn () => null)->name('file-subjects.archive');
        Route::patch('files/subjects/archived/{record}', fn () => null)->name('file-subjects.archived.activate');
        Route::patch('files/subjects/trash/{record}', fn () => null)->name('file-subjects.trash.restore');
        Route::delete('files/subjects/trash/{record}', fn () => null)->name('file-subjects.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new FileSubject)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra']);

        $row = (new FileSubjectListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'notes' => 'Oharra'],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_subject_by_its_euskera_name(): void
    {
        $record = (new FileSubject)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $rows = (new FileSubjectListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['file-subject-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_subject_offers_the_permanent_delete(): void
    {
        $record = (new FileSubject)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true]);

        $rows = (new FileSubjectListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('file-subject-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

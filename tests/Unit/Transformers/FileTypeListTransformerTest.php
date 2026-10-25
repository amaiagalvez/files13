<?php

namespace Files13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Files13\Models\FileType;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Files13\Transformers\FileTypeListTransformer;

final class FileTypeListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/types/'.$name, fn () => null)->name('file-types.'.$name);
        }

        Route::delete('files/types/{record}', fn () => null)->name('file-types.destroy');
        Route::patch('files/types/{record}/archive', fn () => null)->name('file-types.archive');
        Route::patch('files/types/archived/{record}', fn () => null)->name('file-types.archived.activate');
        Route::patch('files/types/trash/{record}', fn () => null)->name('file-types.trash.restore');
        Route::delete('files/types/trash/{record}', fn () => null)->name('file-types.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new FileType)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra']);

        $row = (new FileTypeListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'company' => false, 'person' => false, 'notes' => 'Oharra'],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_type_by_its_euskera_name(): void
    {
        $record = (new FileType)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $rows = (new FileTypeListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['file-type-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_type_offers_the_permanent_delete(): void
    {
        $record = (new FileType)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true]);

        $rows = (new FileTypeListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('file-type-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

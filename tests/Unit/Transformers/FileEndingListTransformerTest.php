<?php

namespace Files13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Files13\Models\FileEnding;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Files13\Transformers\FileEndingListTransformer;

final class FileEndingListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/endings/'.$name, fn () => null)->name('file-endings.'.$name);
        }

        Route::delete('files/endings/{record}', fn () => null)->name('file-endings.destroy');
        Route::patch('files/endings/{record}/archive', fn () => null)->name('file-endings.archive');
        Route::patch('files/endings/archived/{record}', fn () => null)->name('file-endings.archived.activate');
        Route::patch('files/endings/trash/{record}', fn () => null)->name('file-endings.trash.restore');
        Route::delete('files/endings/trash/{record}', fn () => null)->name('file-endings.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new FileEnding)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra']);

        $row = (new FileEndingListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'notes' => 'Oharra'],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_ending_by_its_euskera_name(): void
    {
        $record = (new FileEnding)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $rows = (new FileEndingListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['file-ending-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_ending_offers_the_permanent_delete(): void
    {
        $record = (new FileEnding)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true]);

        $rows = (new FileEndingListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('file-ending-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

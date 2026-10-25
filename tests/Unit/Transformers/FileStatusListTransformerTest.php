<?php

namespace Files13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Files13\Models\FileStatus;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Files13\Transformers\FileStatusListTransformer;

final class FileStatusListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/statuses/'.$name, fn () => null)->name('file-statuses.'.$name);
        }

        Route::delete('files/statuses/{record}', fn () => null)->name('file-statuses.destroy');
        Route::patch('files/statuses/{record}/archive', fn () => null)->name('file-statuses.archive');
        Route::patch('files/statuses/archived/{record}', fn () => null)->name('file-statuses.archived.activate');
        Route::patch('files/statuses/trash/{record}', fn () => null)->name('file-statuses.trash.restore');
        Route::delete('files/statuses/trash/{record}', fn () => null)->name('file-statuses.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new FileStatus)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra']);

        $row = (new FileStatusListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'notes' => 'Oharra'],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_status_by_its_euskera_name(): void
    {
        $record = (new FileStatus)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $rows = (new FileStatusListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['file-status-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_status_offers_the_permanent_delete(): void
    {
        $record = (new FileStatus)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true]);

        $rows = (new FileStatusListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('file-status-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

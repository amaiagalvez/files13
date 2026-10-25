<?php

namespace Files13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Files13\Models\FileLocation;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Files13\Transformers\FileLocationListTransformer;

final class FileLocationListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/locations/'.$name, fn () => null)->name('file-locations.'.$name);
        }

        Route::delete('files/locations/{record}', fn () => null)->name('file-locations.destroy');
        Route::patch('files/locations/{record}/archive', fn () => null)->name('file-locations.archive');
        Route::patch('files/locations/archived/{record}', fn () => null)->name('file-locations.archived.activate');
        Route::patch('files/locations/trash/{record}', fn () => null)->name('file-locations.trash.restore');
        Route::delete('files/locations/trash/{record}', fn () => null)->name('file-locations.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new FileLocation)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra'])->setRelation('days', new Collection);

        $row = (new FileLocationListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'notes' => 'Oharra', 'days' => []],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_location_by_its_euskera_name(): void
    {
        $record = (new FileLocation)->forceFill(['id' => 1, 'name_eu' => 'Gaia'])->setRelation('days', new Collection);

        $rows = (new FileLocationListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['file-location-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_location_offers_the_permanent_delete(): void
    {
        $record = (new FileLocation)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true])->setRelation('days', new Collection);

        $rows = (new FileLocationListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('file-location-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

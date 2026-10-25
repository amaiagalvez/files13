<?php

namespace Files13\Tests\Unit\Transformers;

use Files13\Models\Phase;
use Basics13\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Collection;
use Files13\Transformers\PhaseListTransformer;
use Illuminate\Pagination\LengthAwarePaginator;

final class PhaseListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['index' => 'get', 'archived.index' => 'get', 'trash.index' => 'get'] as $name => $verb) {
            Route::{$verb}('files/phases/'.$name, fn () => null)->name('phases.'.$name);
        }

        Route::delete('files/phases/{record}', fn () => null)->name('phases.destroy');
        Route::patch('files/phases/{record}/archive', fn () => null)->name('phases.archive');
        Route::patch('files/phases/archived/{record}', fn () => null)->name('phases.archived.activate');
        Route::patch('files/phases/trash/{record}', fn () => null)->name('phases.trash.restore');
        Route::delete('files/phases/trash/{record}', fn () => null)->name('phases.trash.destroy');
    }

    public function test_the_row_carries_the_four_names_and_the_edit_payload(): void
    {
        $record = (new Phase)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'notes' => 'Oharra'])->setRelation('types', new Collection)->setRelation('subjects', new Collection);

        $row = (new PhaseListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'][0];

        $this->assertSame('Gaia', $row['nameEu']);
        $this->assertSame('Tema', $row['nameEs']);
        $this->assertSame(
            ['id' => 1, 'name_eu' => 'Gaia', 'name_es' => 'Tema', 'name_fr' => null, 'name_en' => null, 'order' => 0, 'color' => null, 'show_calendar' => false, 'accumulate_days' => false, 'accumulate_days_order' => null, 'notes' => 'Oharra', 'type_ids' => [], 'subjects' => []],
            $row['editPayload'],
        );
    }

    public function test_the_confirmation_names_the_phase_by_its_euskera_name(): void
    {
        $record = (new Phase)->forceFill(['id' => 1, 'name_eu' => 'Gaia'])->setRelation('types', new Collection)->setRelation('subjects', new Collection);

        $rows = (new PhaseListTransformer)->active(new LengthAwarePaginator([$record], 1, 15), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['phase-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Gaia']), $delete['confirmTitle']);
    }

    public function test_a_trashed_phase_offers_the_permanent_delete(): void
    {
        $record = (new Phase)->forceFill(['id' => 1, 'name_eu' => 'Gaia', 'active' => true])->setRelation('types', new Collection)->setRelation('subjects', new Collection);

        $rows = (new PhaseListTransformer)->trash(new LengthAwarePaginator([$record], 1, 15), '')['rows'];

        $this->assertArrayHasKey('phase-force-delete-1', array_column($rows[0]['actions'] ?? [], null, 'test'));
    }
}

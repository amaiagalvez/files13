<?php

namespace Files13\Tests\Feature\FileLocations;

use Files13\Tests\TestCase;
use Files13\Models\LocationDay;
use Files13\Models\FileLocation;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileLocationDaysTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_day_added_from_the_location_form_is_listed_in_that_form(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));

        $this->post(route('file-locations.store'), [
            'name_eu' => 'Urretxu',
            'days' => [['date' => '2026-07-25', 'name' => 'Santiago'], ['date' => '', 'name' => '']],
        ])->assertSessionHasNoErrors();

        $location = FileLocation::query()->sole();
        $this->assertSame(['Santiago'], $location->days->pluck('name')->all());
        $this->get(route('file-locations.index'))->assertOk()->assertSee('Santiago')->assertSee('2026-07-25');
    }

    public function test_the_days_have_no_list_of_their_own(): void
    {
        $names = collect(Route::getRoutes()->getRoutesByName())->keys();

        $this->assertEmpty($names->filter(fn (string $name): bool => str_contains($name, 'day')));
    }

    public function test_saving_the_form_updates_creates_and_removes_days_at_once(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));
        $location = FileLocation::factory()->create();
        $kept = $location->days()->create(['date' => '2026-01-01', 'name' => 'Urteberri']);
        $removed = $location->days()->create(['date' => '2026-12-25', 'name' => 'Eguberri']);

        $this->put(route('file-locations.update', $location), [
            'name_eu' => $location->name_eu,
            'days' => [
                ['id' => $kept->id, 'date' => '2026-01-01', 'name' => 'Urte berri eguna'],
                ['date' => '2026-01-20', 'name' => 'San Sebastian'],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Urte berri eguna', 'San Sebastian'], $location->refresh()->days->pluck('name')->all());
        $this->assertDatabaseMissing('FIL_location_days', ['id' => $removed->id]);
    }

    public function test_a_day_of_another_location_is_refused(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));
        $location = FileLocation::factory()->create();
        $other = FileLocation::factory()->create();
        $foreign = $other->days()->create(['date' => '2026-05-01', 'name' => 'Langileen eguna']);

        $this->put(route('file-locations.update', $location), [
            'name_eu' => $location->name_eu,
            'days' => [['id' => $foreign->id, 'date' => '2026-05-02', 'name' => 'Aldatua']],
        ])->assertSessionHasErrors('days.0.id');

        $this->assertSame('Langileen eguna', $foreign->refresh()->name);
    }

    public function test_a_date_is_not_repeated_and_a_day_needs_its_name(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));

        $this->post(route('file-locations.store'), [
            'name_eu' => 'Zumarraga',
            'days' => [['date' => '2026-08-15', 'name' => 'Andre Mari'], ['date' => '2026-08-15', 'name' => 'Bikoitza'], ['date' => '2026-09-01', 'name' => '']],
        ])->assertSessionHasErrors(['days.0.date', 'days.1.date', 'days.2.name']);

        $this->assertDatabaseCount('FIL_locations', 0);
    }

    public function test_trashing_keeps_the_days_and_the_permanent_delete_takes_them(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));
        $location = FileLocation::factory()->create();
        $location->days()->create(['date' => '2026-03-19', 'name' => 'San Jose']);

        $this->delete(route('file-locations.destroy', $location));
        $this->assertSame(1, LocationDay::query()->count());

        $this->patch(route('file-locations.trash.restore', $location->id));
        $this->assertSame(1, $location->refresh()->days()->count());

        $location->delete();
        $this->delete(route('file-locations.trash.destroy', $location->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertSame(0, LocationDay::query()->count());
    }
}

<?php

namespace Files13\Tests\Browser\FileLocations;

use Basics13\Models\Role;
use Laravel\Dusk\Browser;
use Files13\Tests\DuskTestCase;
use Files13\Models\FileLocation;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * The days of a location are rows of the drawer, added and removed in Alpine before the form is
 * sent, so they go through the browser.
 */
class FileLocationDaysTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_days_are_added_and_removed_from_the_location_drawer(): void
    {
        $user = $this->user();
        $user->assignRole(Role::query()->where('slug', 'admin')->firstOrFail());
        $location = FileLocation::factory()->create(['name_eu' => 'Urretxu']);
        $location->days()->create(['date' => '2026-12-25', 'name' => 'Eguberri']);

        $this->browse(function (Browser $browser) use ($user, $location): void {
            $browser->loginAs($user)
                ->visit('/files/locations')
                ->assertSee(__('Days'))
                ->click("[data-test='file-location-edit-{$location->getKey()}']")
                ->waitFor('dialog[open] [data-test="file-location-day-name-0"]')
                ->assertInputValue('dialog[open] [data-test="file-location-day-name-0"]', 'Eguberri')
                ->click('dialog[open] [data-test="file-location-day-remove-0"]')
                ->click('dialog[open] [data-test="file-location-day-add"]')
                ->waitFor('dialog[open] [data-test="file-location-day-date-0"]')
                ->keys('dialog[open] [data-test="file-location-day-date-0"]', '25072026')
                ->type('dialog[open] [data-test="file-location-day-name-0"]', 'Santiago')
                ->click('dialog[open] [data-test="file-location-submit"]')
                ->waitForTextIn('[data-test="file-location-status"]', __('basics13::messages.updated'));
        });

        $this->assertSame(['Santiago'], $location->refresh()->days->pluck('name')->all());
    }
}

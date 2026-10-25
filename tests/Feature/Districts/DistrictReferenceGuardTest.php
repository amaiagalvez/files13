<?php

namespace Files13\Tests\Feature\Districts;

use Files13\Models\File;
use Files13\Tests\TestCase;
use Locations13\Models\District;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DistrictReferenceGuardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_district_a_file_names_is_not_deleted(): void
    {
        $district = District::factory()->create();
        File::factory()->create(['district_id' => $district->id]);

        $this->assertFalse($district->delete());
        $this->assertDatabaseHas('LOC_districts', ['id' => $district->id, 'deleted_at' => null]);
    }

    public function test_a_trashed_file_still_keeps_its_district(): void
    {
        $district = District::factory()->create();
        File::factory()->create(['district_id' => $district->id])->delete();

        $this->assertFalse($district->delete());
        $this->assertDatabaseHas('LOC_districts', ['id' => $district->id, 'deleted_at' => null]);
    }

    public function test_a_trashed_district_a_file_names_is_not_deleted_for_good(): void
    {
        $district = District::factory()->create();
        File::factory()->create(['district_id' => $district->id]);
        $district->forceFill(['deleted_at' => now()])->saveQuietly();

        $this->assertFalse($district->forceDelete());
        $this->assertDatabaseHas('LOC_districts', ['id' => $district->id]);
    }

    public function test_a_district_no_file_names_can_be_deleted(): void
    {
        $district = District::factory()->create();

        $this->assertTrue((bool) $district->delete());
        $this->assertSoftDeleted('LOC_districts', ['id' => $district->id]);
    }
}

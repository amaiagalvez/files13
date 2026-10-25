<?php

namespace Files13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Files13\Queries\FileLocations\FileLocationListQuery;

class FileLocationListQueryTest extends TestCase
{
    public function test_the_list_offers_the_three_states_and_no_advanced_filters(): void
    {
        $query = new FileLocationListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertTrue((new \ReflectionMethod(FileLocationListQuery::class, $scope))->isPublic());
            $this->assertSame([], $query->searchableFields($scope));
        }

        $this->assertSame(['active' => 1, 'archived' => 2, 'trashed' => 3], $query->stateCounts(1, 2, 3));
    }

    public function test_the_search_looks_in_the_four_languages(): void
    {
        $this->assertSame(
            ['FIL_locations.name_eu', 'FIL_locations.name_es', 'FIL_locations.name_fr', 'FIL_locations.name_en'],
            (new \ReflectionClass(FileLocationListQuery::class))->getConstant('NAME_COLUMNS'),
        );
    }
}

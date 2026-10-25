<?php

namespace Files13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Files13\Queries\FileStatuses\FileStatusListQuery;

class FileStatusListQueryTest extends TestCase
{
    public function test_the_list_offers_the_three_states_and_no_advanced_filters(): void
    {
        $query = new FileStatusListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertTrue((new \ReflectionMethod(FileStatusListQuery::class, $scope))->isPublic());
            $this->assertSame([], $query->searchableFields($scope));
        }

        $this->assertSame(['active' => 1, 'archived' => 2, 'trashed' => 3], $query->stateCounts(1, 2, 3));
    }

    public function test_the_search_looks_in_the_four_languages(): void
    {
        $this->assertSame(
            ['FIL_statuses.name_eu', 'FIL_statuses.name_es', 'FIL_statuses.name_fr', 'FIL_statuses.name_en'],
            (new \ReflectionClass(FileStatusListQuery::class))->getConstant('NAME_COLUMNS'),
        );
    }
}

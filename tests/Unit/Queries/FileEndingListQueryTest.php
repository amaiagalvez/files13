<?php

namespace Files13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Files13\Queries\FileEndings\FileEndingListQuery;

class FileEndingListQueryTest extends TestCase
{
    public function test_the_list_offers_the_three_states_and_no_advanced_filters(): void
    {
        $query = new FileEndingListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertTrue((new \ReflectionMethod(FileEndingListQuery::class, $scope))->isPublic());
            $this->assertSame([], $query->searchableFields($scope));
        }

        $this->assertSame(['active' => 1, 'archived' => 2, 'trashed' => 3], $query->stateCounts(1, 2, 3));
    }

    public function test_the_search_looks_in_the_four_languages(): void
    {
        $this->assertSame(
            ['FIL_endings.name_eu', 'FIL_endings.name_es', 'FIL_endings.name_fr', 'FIL_endings.name_en'],
            (new \ReflectionClass(FileEndingListQuery::class))->getConstant('NAME_COLUMNS'),
        );
    }
}

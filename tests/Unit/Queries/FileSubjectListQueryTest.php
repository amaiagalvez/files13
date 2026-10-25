<?php

namespace Files13\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Files13\Queries\FileSubjects\FileSubjectListQuery;

class FileSubjectListQueryTest extends TestCase
{
    public function test_the_list_offers_the_three_states_and_no_advanced_filters(): void
    {
        $query = new FileSubjectListQuery;

        foreach (['active', 'archived', 'trashed'] as $scope) {
            $this->assertTrue((new \ReflectionMethod(FileSubjectListQuery::class, $scope))->isPublic());
            $this->assertSame([], $query->searchableFields($scope));
        }

        $this->assertSame(['active' => 1, 'archived' => 2, 'trashed' => 3], $query->stateCounts(1, 2, 3));
    }

    public function test_the_search_looks_in_the_four_languages(): void
    {
        $this->assertSame(
            ['FIL_subjects.name_eu', 'FIL_subjects.name_es', 'FIL_subjects.name_fr', 'FIL_subjects.name_en'],
            (new \ReflectionClass(FileSubjectListQuery::class))->getConstant('NAME_COLUMNS'),
        );
    }
}

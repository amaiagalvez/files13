<?php

namespace Files13\Tests\Unit;

use Basics13\Tests\PackageIsolationTestCase;

/**
 * files13 must work without entities13, projects13, the bridge or the host installed. It may use
 * locations13, the reference domain, and nothing else of the other domains.
 */
class PackageIsolationTest extends PackageIsolationTestCase
{
    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function forbiddenInProductionCode(): array
    {
        return [
            'App\\',
            'Entities13\\',
            'EntityProjects13\\',
            'Projects13\\',
            'ENT_entities',
            'ENT_entity_types',
            'PRO_projects',
            "route('entities.",
            "route('projects.",
            "route('epics.",
        ];
    }

    protected function forbiddenInTests(): array
    {
        return [
            'Entities13\\',
            'EntityProjects13\\',
            'Projects13\\',
        ];
    }

    protected function isolationAdvice(): string
    {
        return 'files13 is a domain package: it depends only on basics13, locations13 and the host, '
            .'and must work without entities13, projects13 or the bridge installed.';
    }

    public function test_composer_requires_none_of_the_other_domains(): void
    {
        $composer = json_decode((string) file_get_contents($this->packageRoot().'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($composer);

        $required = array_keys([...($composer['require'] ?? []), ...($composer['require-dev'] ?? [])]);

        foreach (['amaia/entities13', 'amaia/projects13', 'amaia/entity-projects13'] as $package) {
            $this->assertNotContains($package, $required, "files13 must not require {$package}");
        }
    }
}

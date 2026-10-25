<?php

namespace Files13\Tests\Unit;

use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Files13\Models\FileEnding;
use Files13\Models\FileStatus;
use Files13\Models\FileSubject;
use Files13\Models\FileLocation;
use Basics13\Tests\ChecksResourceArchitecture;

class ArchitectureTest extends TestCase
{
    use ChecksResourceArchitecture;

    protected function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    protected function resources(): array
    {
        return [
            'FileSubject' => FileSubject::class,
            'FileType' => FileType::class,
            'FileStatus' => FileStatus::class,
            'FileEnding' => FileEnding::class,
            'FileLocation' => FileLocation::class,
            'Phase' => Phase::class,
            'File' => File::class,
        ];
    }
}

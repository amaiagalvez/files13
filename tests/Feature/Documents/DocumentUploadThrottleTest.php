<?php

namespace Files13\Tests\Feature\Documents;

use Files13\Models\File;
use Files13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class DocumentUploadThrottleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uploads_to_a_file_are_limited_to_thirty_a_minute(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post(route('files.documents.store', $file), [])->assertStatus(302);
        }

        $this->post(route('files.documents.store', $file), [])->assertStatus(429);
    }
}

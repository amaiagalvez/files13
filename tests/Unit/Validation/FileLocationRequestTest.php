<?php

namespace Files13\Tests\Unit\Validation;

use Files13\Tests\TestCase;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Basics13\Support\Testing\PermissionsActor;
use Files13\Http\Requests\FileLocationRequest;

/**
 * Every payload below fails a rule of its own field first, so the unique lookup, the one rule
 * that needs the database, is never reached.
 */
class FileLocationRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make('config')->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
    }

    public function test_authorize_needs_the_create_permission(): void
    {
        $request = new FileLocationRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['file_locations.view']));
        $this->assertFalse($request->authorize());

        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['file_locations.create']));
        $this->assertTrue($request->authorize());

        $this->assertFalse((new FileLocationRequest)->authorize());
    }

    public function test_the_euskera_name_is_required_and_at_least_two_characters(): void
    {
        $request = FileLocationRequest::create('/', 'POST', []);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');

        $request = FileLocationRequest::create('/', 'POST', ['name_eu' => 'A']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');
    }

    public function test_the_lengths_come_from_the_configuration(): void
    {
        foreach (['name_es', 'name_fr', 'name_en'] as $field) {
            $request = FileLocationRequest::create('/', 'POST', ['name_eu' => 'A', $field => str_repeat('a', MaxLength::string() + 1)]);
            $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), $field);
        }

        $request = FileLocationRequest::create('/', 'POST', ['name_eu' => 'A', 'notes' => str_repeat('a', MaxLength::longText() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'notes');
    }

    public function test_every_name_is_unique_in_its_own_language(): void
    {
        $rules = FileLocationRequest::create('/', 'POST', [])->rules();

        foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $field) {
            $this->assertCount(1, array_filter($rules[$field], static fn (mixed $rule): bool => $rule instanceof Unique), $field);
        }
    }

    public function test_names_are_trimmed_and_a_blank_translation_becomes_null(): void
    {
        $request = FileLocationRequest::create('/', 'POST', ['name_eu' => '  Gaia  ', 'name_es' => '   ']);
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

        $this->assertSame('Gaia', $request->input('name_eu'));
        $this->assertNull($request->input('name_es'));
    }
}

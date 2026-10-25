<?php

namespace Files13\Tests\Unit\Validation;

use Files13\Tests\TestCase;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Files13\Http\Requests\FileTypeRequest;
use Basics13\Support\Testing\PermissionsActor;

/**
 * Every payload below fails a rule of its own field first, so the unique lookup, the one rule
 * that needs the database, is never reached.
 */
class FileTypeRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make('config')->set('basics13.validation.max_length', ['string' => 123, 'longtext' => 456]);
    }

    public function test_authorize_needs_the_create_permission(): void
    {
        $request = new FileTypeRequest;
        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['file_types.view']));
        $this->assertFalse($request->authorize());

        $request->setUserResolver(static fn (): PermissionsActor => PermissionsActor::holding(['file_types.create']));
        $this->assertTrue($request->authorize());

        $this->assertFalse((new FileTypeRequest)->authorize());
    }

    public function test_the_euskera_name_is_required_and_at_least_two_characters(): void
    {
        $request = FileTypeRequest::create('/', 'POST', []);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');

        $request = FileTypeRequest::create('/', 'POST', ['name_eu' => 'A']);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'name_eu');
    }

    public function test_the_lengths_come_from_the_configuration(): void
    {
        foreach (['name_es', 'name_fr', 'name_en'] as $field) {
            $request = FileTypeRequest::create('/', 'POST', ['name_eu' => 'A', $field => str_repeat('a', MaxLength::string() + 1)]);
            $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), $field);
        }

        $request = FileTypeRequest::create('/', 'POST', ['name_eu' => 'A', 'notes' => str_repeat('a', MaxLength::longText() + 1)]);
        $this->assertRequestValidationFails($request, static fn (): array => $request->rules(), 'notes');
    }

    public function test_every_name_is_unique_in_its_own_language(): void
    {
        $rules = FileTypeRequest::create('/', 'POST', [])->rules();

        foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $field) {
            $this->assertCount(1, array_filter($rules[$field], static fn (mixed $rule): bool => $rule instanceof Unique), $field);
        }
    }

    public function test_names_are_trimmed_and_a_blank_translation_becomes_null(): void
    {
        $request = FileTypeRequest::create('/', 'POST', ['name_eu' => '  Gaia  ', 'name_es' => '   ']);
        (new \ReflectionMethod($request, 'prepareForValidation'))->invoke($request);

        $this->assertSame('Gaia', $request->input('name_eu'));
        $this->assertNull($request->input('name_es'));
    }
}

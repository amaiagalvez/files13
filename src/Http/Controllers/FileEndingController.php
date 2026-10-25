<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileEnding;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FileEndingRequest;
use Files13\Http\Requests\FileEndingListRequest;
use Files13\Transformers\FileEndingListTransformer;
use Files13\Queries\FileEndings\FileEndingListQuery;
use Basics13\Support\Database\UniqueConstraintViolation;
use Files13\Http\Requests\FileEndingSelectOptionsRequest;
use Files13\Queries\FileEndings\FileEndingSelectOptionsQuery;

class FileEndingController extends Controller
{
    public function index(
        FileEndingListRequest $request,
        FileEndingListQuery $query,
        FileEndingListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileEndings = $query->active($search);

        return $this->listView($request, 'files13::file-endings.list', [
            'fileEndings' => $fileEndings,
            'list' => $transformer->active(
                $fileEndings,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $fileEndings->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        FileEndingSelectOptionsRequest $request,
        FileEndingSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(FileEndingRequest $request, FileEndingListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name_eu')->toString();
        $deletedEnding = $query->findTrashedByName($name);

        if ($deletedEnding !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->trashedEndingConflict($request, $deletedEnding);
        }

        try {
            $fileEnding = FileEnding::create($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($fileEnding->only(['id', 'name_eu']), 201);
        }

        return $this->backToList('file-endings.index')->with('status', __('basics13::messages.created'));
    }

    public function update(FileEndingRequest $request, FileEnding $fileEnding): RedirectResponse
    {
        try {
            $fileEnding->update($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        return $this->backToList('file-endings.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(FileEnding $fileEnding): RedirectResponse
    {
        $this->authorize('delete', $fileEnding);

        // A ending in use may go to the trash: only the permanent delete is refused while a
        // file, phase or document still points at it.
        DB::transaction(static fn (): ?bool => $fileEnding->delete());

        return $this->backToList('file-endings.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * A trashed ending already uses the euskera name the user is creating. The browser is sent back
     * to the list with the conflict modal open; a JSON caller gets a 409 it can render on the field.
     *
     * A ending is named in four languages and the conflict modal offers to create it with the names
     * the trashed one had, so the whole set travels in the payload instead of the single `name` the
     * generic conflict of the base controller carries.
     */
    private function trashedEndingConflict(
        FileEndingRequest $request,
        FileEnding $trashedEnding,
    ): RedirectResponse|JsonResponse {
        $payload = [
            'id' => $trashedEnding->getKey(),
            ...$trashedEnding->only(['name_eu', 'name_es', 'name_fr', 'name_en']),
        ];

        if ($request->expectsJson()) {
            $message = __('basics13::messages.deleted_name_conflict', ['name' => $trashedEnding->name_eu]);

            return response()->json([
                'message' => $message,
                'errors' => ['name_eu' => [$message]],
            ], 409);
        }

        return $this->backToList('file-endings.index')
            ->withInput()
            ->with('deleted_file_ending_conflict', $payload);
    }

    /**
     * A write that passed validation and still hit a unique index lost a race against a concurrent
     * one, which the request cannot have seen. The index the database names is what tells which of
     * the four languages collided.
     */
    private function failOnDuplicatedName(QueryException $exception): never
    {
        UniqueConstraintViolation::rethrowAsValidationError($exception, $this->duplicatedNameField($exception));
    }

    /**
     * MySQL names the index of the colliding language (`..._active_name_es_unique`), while the
     * partial index SQLite and PostgreSQL build names the bare column (`...name_es`), so both
     * spellings are looked for.
     */
    private function duplicatedNameField(QueryException $exception): string
    {
        $message = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

        foreach (FileEnding::NAME_FIELDS as $field) {
            if (str_contains($message, 'active_'.$field) || str_contains($message, '.'.$field)) {
                return $field;
            }
        }

        return 'name_eu';
    }
}

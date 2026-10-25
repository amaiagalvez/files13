<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FileLocationRequest;
use Files13\Http\Requests\FileLocationListRequest;
use Files13\Transformers\FileLocationListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use Files13\Queries\FileLocations\FileLocationListQuery;
use Files13\Http\Requests\FileLocationSelectOptionsRequest;
use Files13\Queries\FileLocations\FileLocationSelectOptionsQuery;

class FileLocationController extends Controller
{
    public function index(
        FileLocationListRequest $request,
        FileLocationListQuery $query,
        FileLocationListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileLocations = $query->active($search);

        return $this->listView($request, 'files13::file-locations.list', [
            'fileLocations' => $fileLocations,
            'list' => $transformer->active(
                $fileLocations,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $fileLocations->total() : null),
            ),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        FileLocationSelectOptionsRequest $request,
        FileLocationSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(FileLocationRequest $request, FileLocationListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name_eu')->toString();
        $deletedLocation = $query->findTrashedByName($name);

        if ($deletedLocation !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->trashedLocationConflict($request, $deletedLocation);
        }

        try {
            // The location and its days are written in one transaction: a location is saved with
            // the days of its form or with neither.
            $fileLocation = DB::transaction(static function () use ($request): FileLocation {
                $fileLocation = FileLocation::create($request->safe()->except(['days']));
                self::syncDays($fileLocation, $request->validated()['days'] ?? null);

                return $fileLocation;
            });
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($fileLocation->only(['id', 'name_eu']), 201);
        }

        return $this->backToList('file-locations.index')->with('status', __('basics13::messages.created'));
    }

    public function update(FileLocationRequest $request, FileLocation $fileLocation): RedirectResponse
    {
        try {
            DB::transaction(static function () use ($request, $fileLocation): void {
                $fileLocation->update($request->safe()->except(['days']));
                self::syncDays($fileLocation, $request->validated()['days'] ?? null);
            });
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        return $this->backToList('file-locations.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(FileLocation $fileLocation): RedirectResponse
    {
        $this->authorize('delete', $fileLocation);

        // A location in use may go to the trash: only the permanent delete is refused while a
        // file, phase or document still points at it.
        DB::transaction(static fn (): ?bool => $fileLocation->delete());

        return $this->backToList('file-locations.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * A trashed location already uses the euskera name the user is creating. The browser is sent back
     * to the list with the conflict modal open; a JSON caller gets a 409 it can render on the field.
     *
     * A location is named in four languages and the conflict modal offers to create it with the names
     * the trashed one had, so the whole set travels in the payload instead of the single `name` the
     * generic conflict of the base controller carries.
     */
    private function trashedLocationConflict(
        FileLocationRequest $request,
        FileLocation $trashedLocation,
    ): RedirectResponse|JsonResponse {
        $payload = [
            'id' => $trashedLocation->getKey(),
            ...$trashedLocation->only(['name_eu', 'name_es', 'name_fr', 'name_en']),
        ];

        if ($request->expectsJson()) {
            $message = __('basics13::messages.deleted_name_conflict', ['name' => $trashedLocation->name_eu]);

            return response()->json([
                'message' => $message,
                'errors' => ['name_eu' => [$message]],
            ], 409);
        }

        return $this->backToList('file-locations.index')
            ->withInput()
            ->with('deleted_file_location_conflict', $payload);
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

        foreach (FileLocation::NAME_FIELDS as $field) {
            if (str_contains($message, 'active_'.$field) || str_contains($message, '.'.$field)) {
                return $field;
            }
        }

        return 'name_eu';
    }

    /**
     * The form sends the whole list of days: rows with an id are updated, rows without one are
     * created, and the days the list no longer holds are deleted. A form that sends no list at all
     * leaves the days alone.
     *
     * @param  list<array{id?: int|null, date: string, name: string}>|null  $days
     */
    private static function syncDays(FileLocation $fileLocation, ?array $days): void
    {
        if ($days === null) {
            return;
        }

        $kept = array_values(array_filter(array_map(static fn (array $day): ?int => isset($day['id']) ? (int) $day['id'] : null, $days)));
        $fileLocation->days()->whereNotIn('id', $kept)->delete();

        foreach ($days as $day) {
            $attributes = ['date' => $day['date'], 'name' => $day['name']];

            if (isset($day['id'])) {
                $fileLocation->days()->whereKey($day['id'])->update($attributes);

                continue;
            }

            $fileLocation->days()->create($attributes);
        }
    }
}

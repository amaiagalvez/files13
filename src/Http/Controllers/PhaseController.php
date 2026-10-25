<?php

namespace Files13\Http\Controllers;

use Files13\Models\Phase;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\PhaseRequest;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Files13\Queries\Phases\PhaseListQuery;
use Files13\Http\Requests\PhaseListRequest;
use Files13\Transformers\PhaseListTransformer;
use Files13\Queries\Phases\PhaseFormOptionsQuery;
use Files13\Queries\Phases\PhaseSelectOptionsQuery;
use Files13\Http\Requests\PhaseSelectOptionsRequest;
use Basics13\Support\Database\UniqueConstraintViolation;

class PhaseController extends Controller
{
    public function index(
        PhaseListRequest $request,
        PhaseListQuery $query,
        PhaseListTransformer $transformer,
        PhaseFormOptionsQuery $options,
    ): View|string {
        $search = $request->search();
        $phases = $query->active($search);

        return $this->listView($request, 'files13::phases.list', [
            'phases' => $phases,
            'list' => $transformer->active(
                $phases,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $phases->total() : null),
            ),
            'options' => $options->options(),
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        PhaseSelectOptionsRequest $request,
        PhaseSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(PhaseRequest $request, PhaseListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name_eu')->toString();
        $deletedPhase = $query->findTrashedByName($name);

        if ($deletedPhase !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->trashedPhaseConflict($request, $deletedPhase);
        }

        try {
            // The phase, its types and its subjects are written in one transaction.
            $phase = DB::transaction(static function () use ($request): Phase {
                $phase = Phase::create($request->safe()->except(['type_ids', 'subjects']));
                self::syncRelations($phase, $request->validated());

                return $phase;
            });
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($phase->only(['id', 'name_eu']), 201);
        }

        return $this->backToList('phases.index')->with('status', __('basics13::messages.created'));
    }

    public function update(PhaseRequest $request, Phase $phase): RedirectResponse
    {
        try {
            DB::transaction(static function () use ($request, $phase): void {
                $phase->update($request->safe()->except(['type_ids', 'subjects']));
                self::syncRelations($phase, $request->validated());
            });
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        return $this->backToList('phases.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(Phase $phase): RedirectResponse
    {
        $this->authorize('delete', $phase);

        // A phase in use may go to the trash: only the permanent delete is refused while a
        // file, phase or document still points at it.
        DB::transaction(static fn (): ?bool => $phase->delete());

        return $this->backToList('phases.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * A trashed phase already uses the euskera name the user is creating. The browser is sent back
     * to the list with the conflict modal open; a JSON caller gets a 409 it can render on the field.
     *
     * A phase is named in four languages and the conflict modal offers to create it with the names
     * the trashed one had, so the whole set travels in the payload instead of the single `name` the
     * generic conflict of the base controller carries.
     */
    private function trashedPhaseConflict(
        PhaseRequest $request,
        Phase $trashedPhase,
    ): RedirectResponse|JsonResponse {
        $payload = [
            'id' => $trashedPhase->getKey(),
            ...$trashedPhase->only(['name_eu', 'name_es', 'name_fr', 'name_en']),
        ];

        if ($request->expectsJson()) {
            $message = __('basics13::messages.deleted_name_conflict', ['name' => $trashedPhase->name_eu]);

            return response()->json([
                'message' => $message,
                'errors' => ['name_eu' => [$message]],
            ], 409);
        }

        return $this->backToList('phases.index')
            ->withInput()
            ->with('deleted_phase_conflict', $payload);
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

        foreach (Phase::NAME_FIELDS as $field) {
            if (str_contains($message, 'active_'.$field) || str_contains($message, '.'.$field)) {
                return $field;
            }
        }

        return 'name_eu';
    }

    /**
     * The form sends the whole list of types and subjects of the phase: what it no longer holds is
     * detached, the rest is attached or updated.
     *
     * @param  array<string, mixed>  $validated
     */
    private static function syncRelations(Phase $phase, array $validated): void
    {
        /** @var list<int> $typeIds */
        $typeIds = $validated['type_ids'] ?? [];
        $phase->types()->sync($typeIds);

        /** @var list<array{subject_id: int, alert: int, alert_unit: string}> $subjects */
        $subjects = $validated['subjects'] ?? [];
        $phase->subjects()->sync(collect($subjects)->mapWithKeys(static fn (array $row): array => [
            (int) $row['subject_id'] => ['alert' => (int) $row['alert'], 'alert_unit' => $row['alert_unit']],
        ])->all());
    }
}

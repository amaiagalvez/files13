<?php

namespace Files13\Http\Controllers;

use Files13\Models\File;
use Files13\Models\Phase;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\FileRequest;
use Illuminate\Database\QueryException;
use Files13\Queries\Files\FileListQuery;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FileListRequest;
use Files13\Http\Requests\FileShowRequest;
use Files13\Transformers\FileListTransformer;
use Files13\Queries\Files\FileFormOptionsQuery;
use Basics13\Support\Database\UniqueConstraintViolation;

class FileController extends Controller
{
    public function index(
        FileListRequest $request,
        FileListQuery $query,
        FileListTransformer $transformer,
        FileFormOptionsQuery $options,
    ): View|string {
        return $this->activeList($request, $query, $transformer, $options, mine: false);
    }

    /**
     * The files the user created or is assigned to.
     */
    public function mine(
        FileListRequest $request,
        FileListQuery $query,
        FileListTransformer $transformer,
        FileFormOptionsQuery $options,
    ): View|string {
        return $this->activeList($request, $query, $transformer, $options, mine: true);
    }

    /**
     * The file sheet: its data, phases, users, applicants, documents and change log. A user who may
     * view the file but not change it sees it read-only.
     */
    public function show(FileShowRequest $request, File $file, FileListQuery $query, FileFormOptionsQuery $options): View
    {
        $writable = $request->user()?->can('update', $file) ?? false;

        return view('files13::files.show', [
            'file' => $query->sheet($file),
            'writable' => $writable,
            'options' => $writable ? $options->options() : [],
        ]);
    }

    public function store(FileRequest $request, FileListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedFile = $query->findTrashedByName($name);

        if ($deletedFile !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedFile, 'files.index', 'deleted_file_conflict');
        }

        $userId = (int) $request->user()?->getAuthIdentifier();

        try {
            // The file, its rows and the phases of its type are written in one transaction.
            $file = DB::transaction(static function () use ($request, $userId): File {
                $file = File::create($request->safe()->except(['user_ids', 'applicants', 'reuse_deleted_name']));
                $file->users()->sync($request->validated()['user_ids'] ?? []);
                self::attachTypePhases($file, $userId);
                self::syncApplicants($file, $request->validated()['applicants'] ?? []);

                return $file;
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($file->only(['id', 'name']), 201);
        }

        return redirect()->route('files.show', $file)->with('status', __('basics13::messages.created'));
    }

    public function update(FileRequest $request, File $file): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(static function () use ($request, $file, $validated): void {
                $file->update($request->safe()->except(['user_ids', 'applicants', 'reuse_deleted_name']));

                if (array_key_exists('user_ids', $validated)) {
                    $file->users()->sync($validated['user_ids']);
                }

                if (array_key_exists('applicants', $validated)) {
                    self::syncApplicants($file, $validated['applicants']);
                }
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return back()->with('status', __('basics13::messages.updated'));
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->authorize('delete', $file);

        DB::transaction(static fn (): ?bool => $file->delete());

        return $this->backToList('files.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    private function activeList(
        FileListRequest $request,
        FileListQuery $query,
        FileListTransformer $transformer,
        FileFormOptionsQuery $options,
        bool $mine,
    ): View|string {
        $search = $request->search();
        $viewer = $request->user();
        $files = $query->active($search, $viewer, $mine);

        return $this->listView($request, 'files13::files.list', [
            'files' => $files,
            'list' => $transformer->active(
                $files,
                $search,
                $query->visibleCounts($viewer),
            ),
            'options' => $options->options(),
            'mine' => $mine,
            'scope' => 'active',
        ]);
    }

    /**
     * A new file goes through every active phase of its type, in order, with the user who creates
     * it as the one responsible.
     */
    private static function attachTypePhases(File $file, int $userId): void
    {
        $phases = Phase::query()
            ->where('active', true)
            ->whereHas('types', static fn ($types) => $types->whereKey($file->type_id))
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('id');

        foreach ($phases as $phaseId) {
            $file->filePhases()->create(['phase_id' => $phaseId, 'user_id' => $userId ?: null]);
        }
    }

    /**
     * @param  list<array{applicant_type: string, applicant_id: int}>  $rows
     */
    private static function syncApplicants(File $file, array $rows): void
    {
        $file->applicants()->delete();

        foreach ($rows as $row) {
            $file->applicants()->create(['applicant_type' => $row['applicant_type'], 'applicant_id' => (int) $row['applicant_id']]);
        }
    }
}

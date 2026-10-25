<?php

namespace Files13\Http\Controllers;

use Files13\Models\Phase;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Files13\Queries\Phases\PhaseListQuery;
use Files13\Http\Requests\PhaseListRequest;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\PhaseDestroyRequest;
use Files13\Http\Requests\PhaseRestoreRequest;
use Files13\Transformers\PhaseListTransformer;

/**
 * @extends TrashController<Phase>
 */
class PhaseTrashController extends TrashController
{
    public function index(
        PhaseListRequest $request,
        PhaseListQuery $query,
        PhaseListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $phases = $query->trashed($search);

        return $this->listView($request, 'files13::phases.list', [
            'phases' => $phases,
            'list' => $transformer->trash(
                $phases,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $phases->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(PhaseRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(PhaseDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Phase  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A phase is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live phase makes the restore impossible.
     *
     * @param  Phase  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (Phase::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && Phase::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return Phase::class;
    }

    protected function trashRoute(): string
    {
        return 'phases.trash.index';
    }
}

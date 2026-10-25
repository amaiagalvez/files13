<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Queries\Home\FileHomeQuery;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FileListRequest;
use Files13\Http\Requests\FileCalendarRequest;
use Files13\Queries\Calendar\PhaseCalendarQuery;

/**
 * The home of the files and the calendar of their phases.
 */
class FileHomeController extends Controller
{
    public function index(FileListRequest $request, FileHomeQuery $query): View
    {
        return view('files13::files.home', [
            'files' => $query->myFiles($request->user()),
            'alerts' => $query->upcomingAlerts($request->user()),
        ]);
    }

    public function calendar(FileCalendarRequest $request, PhaseCalendarQuery $query): View
    {
        $month = $request->month();
        $from = $month->startOfWeek();
        $to = $month->endOfMonth()->endOfWeek();

        return view('files13::files.calendar', [
            'month' => $month,
            'from' => $from,
            'to' => $to,
            'events' => $query->events($from, $to, $request->user()),
        ]);
    }
}

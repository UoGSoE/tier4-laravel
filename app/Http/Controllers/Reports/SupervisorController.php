<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\User;

class SupervisorController extends Controller
{
    public function index()
    {
        return view('admin.reports.supervisors', [
            'supervisors' => User::with(['students.latestMeeting'])->orderBy('surname')->get(),
        ]);
    }

    public function show(User $supervisor)
    {
        return view('admin.reports.supervisor', [
            'supervisor' => $supervisor,
            'meetings' => $supervisor->meetings()->orderByDesc('meeting_at')->with('student')->get(),
        ]);
    }
}

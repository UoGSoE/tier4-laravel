<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Student;

class OverdueController extends Controller
{
    public function index(string $type)
    {
        $optionName = $type === Student::TYPE_PHD ? 'phd_meeting_reminder_days' : 'postgrad_project_meeting_reminder_days';

        return view('admin.reports.overdue', [
            'type' => $type,
        ]);
    }
}

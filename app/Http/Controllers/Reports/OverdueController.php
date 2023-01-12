<?php

namespace App\Http\Controllers\Reports;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OptionsController extends Controller
{
    public function edit()
    {
        return view('admin.options.edit', [
            'months' => [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'postgrad_project_start_day' => 'required|integer|min:1|max:31',
            'postgrad_project_start_month' => 'required|integer|min:1|max:12',
            'postgrad_project_end_day' => 'required|integer|min:1|max:31',
            'postgrad_project_end_month' => 'required|integer|min:1|max:12',
            'phd_meeting_reminder_days' => 'required|integer|min:1|max:365',
            'postgrad_project_meeting_reminder_days' => 'required|integer|min:1|max:365',
        ]);

        option(['postgrad_project_start_day' => $request->postgrad_project_start_day]);
        option(['postgrad_project_start_month' => $request->postgrad_project_start_month]);
        option(['postgrad_project_end_day' => $request->postgrad_project_end_day]);
        option(['postgrad_project_end_month' => $request->postgrad_project_end_month]);
        option(['phd_meeting_reminder_days' => $request->phd_meeting_reminder_days]);
        option(['postgrad_project_meeting_reminder_days' => $request->postgrad_project_meeting_reminder_days]);

        return redirect()->route('admin.options.edit')->with('success', 'Options updated');
    }
}

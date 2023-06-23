<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OptionsController extends Controller
{
    public function edit(): View
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

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'phd_meeting_reminder_days' => 'required|integer|min:1|max:365',
            'postgrad_project_email_date_1' => 'nullable|date_format:d/m/Y',
            'postgrad_project_email_date_2' => 'nullable|date_format:d/m/Y',
            'postgrad_project_email_date_3' => 'nullable|date_format:d/m/Y',
            'postgrad_project_email_date_4' => 'nullable|date_format:d/m/Y',
            'postgrad_project_email_date_5' => 'nullable|date_format:d/m/Y',
            'postgrad_project_start_day' => 'missing',
            'postgrad_project_start_month' => 'missing',
            'postgrad_project_end_day' => 'missing',
            'postgrad_project_end_month' => 'missing',
            'postgrad_project_meeting_reminder_days' => 'missing',
        ]);

        foreach (range(1, 5) as $i) {
            $fieldName = 'postgrad_project_email_date_' . $i;
            if ($request->filled($fieldName)) {
                option([$fieldName => Carbon::createFromFormat('d/m/Y', $request->input($fieldName))->format('Y-m-d')]);
            } else {
                option([$fieldName => '']);
            }
        }
        option(['phd_meeting_reminder_days' => $request->input('phd_meeting_reminder_days')]);

        return redirect()->route('admin.options.edit')->with('success', 'Options updated');
    }
}

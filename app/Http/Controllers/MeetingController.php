<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function store(Request $request)
    {
        $validStudentIds = $request->user()->students->pluck('id');

        $request->validate([
            'meetings' => 'required|array',
            'meetings.*.student_id' => 'required|integer|in:'.$validStudentIds->join(','),
            'meetings.*.date' => 'nullable|date_format:d/m/Y',
        ]);

        $request->collect('meetings')
            ->reject(fn ($meeting) => is_null($meeting['date']))
            ->each(
                fn ($meeting) => $request->user()->meetings()->create([
                    'student_id' => $meeting['student_id'],
                    'meeting_at' => Carbon::createFromFormat('d/m/Y', $meeting['date']),
                ])
            );

        return redirect()->route('home')->with('success', 'Meetings updated successfully');
    }
}

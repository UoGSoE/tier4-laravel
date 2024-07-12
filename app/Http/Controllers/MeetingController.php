<?php

namespace App\Http\Controllers;

use App\Events\SomethingHappened;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validStudentIds = $request->user()->students->pluck('id');

        $request->validate([
            'meetings' => 'required|array',
            'meetings.*.student_id' => 'required|integer|in:'.$validStudentIds->join(','),
            'meetings.*.date' => 'nullable|date_format:d/m/Y',
        ]);

        $numberUpdated = $request->collect('meetings')
            ->reject(fn ($meeting) => is_null($meeting['date']))
            ->each(function ($meeting) use ($request) {
                $existingMeeting = $request->user()->meetings()
                    ->where('student_id', $meeting['student_id'])
                    ->where('meeting_at', Carbon::createFromFormat('d/m/Y', $meeting['date']))
                    ->first();

                if (! $existingMeeting) {
                    $request->user()->meetings()->create([
                        'student_id' => $meeting['student_id'],
                        'meeting_at' => Carbon::createFromFormat('d/m/Y', $meeting['date']),
                    ]);
                }
            })->count();

        SomethingHappened::dispatch("{$request->user()->full_name} updated {$numberUpdated} meetings");

        return redirect()->route('home')->with('success', 'Meetings updated successfully');
    }
}

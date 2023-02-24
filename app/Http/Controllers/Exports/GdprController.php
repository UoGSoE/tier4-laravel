<?php

namespace App\Http\Controllers\Exports;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;

class GdprController extends Controller
{
    public function student(Student $student)
    {
        $student->load([
            'notes',
            'notes.user',
            'meetings',
            'meetings.supervisor',
        ]);

        return response()->json([
            'data' => [
                'name' => $student->full_name,
                'email' => $student->email,
                'meetings' => $student->meetings->map(function ($meeting) {
                    return [
                        'date' => $meeting->meeting_at->format('Y-m-d'),
                        'with' => $meeting->supervisor->full_name,
                    ];
                }),
                'notes' => $student->notes->map(function ($note) {
                    return [
                        'date' => $note->created_at->format('Y-m-d'),
                        'note' => $note->body,
                        'by' => $note->user->full_name,
                    ];
                }),
            ],
        ]);
    }

    public function staff(User $user)
    {
        $user->load([
            'meetings',
            'meetings.student',
        ]);

        return response()->json([
            'data' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'meetings' => $user->meetings->map(function ($meeting) {
                    return [
                        'date' => $meeting->meeting_at->format('Y-m-d'),
                        'with' => $meeting->student->full_name,
                    ];
                }),
            ],
        ]);
    }
}

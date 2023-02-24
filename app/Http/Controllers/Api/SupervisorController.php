<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SupervisorController extends Controller
{
    public function index()
    {
        $supervisors = User::active()->with('students.latestMeeting')->orderBy('surname')->get()->map(function ($supervisor) {
            return [
                'supervisor_id' => $supervisor->id,
                'supervisor_username' => $supervisor->username,
                'supervisor_name' => $supervisor->full_name,
                'supervisor_email' => $supervisor->email,
                'students' => $supervisor->students->filter(fn ($student) => $student->isActive())->map(function ($student) {
                    return [
                        'student_id' => $student->id,
                        'student_username' => $student->username,
                        'student_name' => $student->full_name,
                        'student_email' => $student->email,
                        'last_meeting_at' => $student->latestMeeting ? $student->latestMeeting->meeting_at->format('Y-m-d') : '',
                    ];
                }),
            ];
        });

        return response()->json([
            'data' => $supervisors,
        ]);
    }

    public function show(User $user)
    {
        $user->load('students.latestMeeting');
        $supervisor = [
            'supervisor_id' => $user->id,
            'supervisor_username' => $user->username,
            'supervisor_name' => $user->full_name,
            'supervisor_email' => $user->email,
            'students' => $user->students->filter(fn ($student) => $student->isActive())->map(function ($student) {
                return [
                    'student_id' => $student->id,
                    'student_username' => $student->username,
                    'student_name' => $student->full_name,
                    'student_email' => $student->email,
                    'last_meeting_at' => $student->latestMeeting ? $student->latestMeeting->meeting_at->format('Y-m-d') : '',
                ];
            }),
        ];

        return response()->json([
            'data' => $supervisor,
        ]);
    }
}

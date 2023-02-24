<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class OverdueMeetingController extends Controller
{
    public function index(Request $request)
    {
        // add ->when() to filter by student type
        $overdueStudents = Student::active()
            ->overdue()
            ->when($request->type === Student::TYPE_PHD, function ($query) {
                return $query->phd();
            })
            ->when($request->type === Student::TYPE_POSTGRAD_PROJECT, function ($query) {
                return $query->postgradProject();
            })
            ->with('supervisor')
            ->get()
            ->map(function ($student) {
                return [
                    'student_id' => $student->id,
                    'student_username' => $student->username,
                    'student_name' => $student->full_name,
                    'student_email' => $student->email,
                    'supervisor_id' => $student->supervisor->id,
                    'supervisor_username' => $student->supervisor->username,
                    'supervisor_name' => $student->supervisor->full_name,
                    'supervisor_email' => $student->supervisor->email,
                    'last_meeting_at' => $student->latestMeeting ? $student->latestMeeting->meeting_at->format('Y-m-d') : '',
                ];
            });

        return response()->json([
            'data' => $overdueStudents,
        ]);
    }
}

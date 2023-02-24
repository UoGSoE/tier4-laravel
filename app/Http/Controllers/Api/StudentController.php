<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::active()
            ->when($request->type === Student::TYPE_PHD, function ($query) {
                return $query->phd();
            })
            ->when($request->type === Student::TYPE_POSTGRAD_PROJECT, function ($query) {
                return $query->postgradProject();
            })
            ->with(['supervisor', 'latestMeeting'])
            ->orderBy('surname')
            ->get()->map(function ($student) {
                return [
                    'last_meeting_at' => $student->latestMeeting ? $student->latestMeeting->meeting_at->format('Y-m-d') : '',
                    'student_id' => $student->id,
                    'student_name' => $student->full_name,
                    'student_username' => $student->username,
                    'student_email' => $student->email,
                    'supervisor_id' => $student->supervisor_id,
                    'supervisor_name' => $student->supervisor->full_name,
                    'supervisor_username' => $student->supervisor->username,
                    'supervisor_email' => $student->supervisor->email,
                ];
            });

        return response()->json([
            'data' => $students,
        ]);
    }
}

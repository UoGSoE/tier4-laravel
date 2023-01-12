<?php

namespace App\Http\Controllers\Reports;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    public function index(Student $student)
    {
        return view('admin.reports.student', [
            'student' => $student,
        ]);
    }
}

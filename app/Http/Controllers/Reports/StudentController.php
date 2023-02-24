<?php

namespace App\Http\Controllers\Reports;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Models\Student;

class StudentController extends Controller
{
    public function index(Student $student): View
    {
        return view('admin.reports.student', [
            'student' => $student,
        ]);
    }
}

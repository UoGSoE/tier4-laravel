<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Student $student): View
    {
        return view('admin.reports.student', [
            'student' => $student,
        ]);
    }
}

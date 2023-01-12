<?php

namespace App\Http\Controllers\Exports;

use App\Exports\PhdStudents;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Ohffs\SimpleSpout\ExcelSheet;

class PhdsController extends Controller
{
    public function show()
    {
        $sheet = (new PhdStudents())->export();

        return response()->download($sheet, 'tier4-phd-students-' . now()->format('d-m-Y') . '.xlsx');
    }
}

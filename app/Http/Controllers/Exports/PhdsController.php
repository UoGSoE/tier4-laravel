<?php

namespace App\Http\Controllers\Exports;

use App\Exports\PhdStudents;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PhdsController extends Controller
{
    public function show(): Response
    {
        $sheet = (new PhdStudents())->export();

        return response()->download($sheet, 'tier4-phd-students-'.now()->format('d-m-Y').'.xlsx');
    }
}

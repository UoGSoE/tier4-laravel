<?php

namespace App\Http\Controllers\Imports;

use App\Events\SomethingHappened;
use App\Http\Controllers\Controller;
use App\Jobs\ImportProjectStudents;
use Illuminate\Http\Request;

class ProjectStudentsController extends Controller
{
    public function create()
    {
        return view('admin.import.project_students');
    }

    public function store(Request $request)
    {
        $request->validate([
            'sheet' => 'required|file|mimes:xlsx,xls',
        ]);

        $sheetData = (new \Ohffs\SimpleSpout\ExcelSheet())->import($request->file('sheet')->path());

        ImportProjectStudents::dispatch($sheetData, $request->user()->email);

        SomethingHappened::dispatch("{$request->user()->full_name} started a project students import");

        return redirect()->route('admin.import.project-students.create')->with('success', 'Import started - you will get an email once it finishes');
    }
}

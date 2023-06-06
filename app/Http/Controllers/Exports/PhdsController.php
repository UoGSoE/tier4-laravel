<?php

namespace App\Http\Controllers\Exports;

use App\Exports\PhdStudents;
use App\Http\Controllers\Controller;
use App\Jobs\ImportPhdStudentsJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhdsController extends Controller
{
    public function show(Request $request): BinaryFileResponse
    {
        $rows = (new PhdStudents())->query()->get()->toArray();

        ImportPhdStudentsJob::dispatch($rows, $request->user());

        return response()->download(storage_path('app/tier4-phd-students.xlsx'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BulkEditStudentsController extends Controller
{
    public function edit(string $type)
    {
        return view('admin.bulk-edit', [
            'type' => $type,
            'formattedType' => $type === Student::TYPE_PHD ? 'PhD' : 'Project',
            'students' => Student::where('type', '=', $type)
                ->whereHas('latestMeeting', fn ($query) => $query->where('meeting_at', '>=', now()->subMonths(6)))
                ->with('supervisor')
                ->orderBy('surname')
                ->get(),
        ]);
    }

    public function update(Request $request, string $type)
    {
        $request->validate([
            'is_active' => 'required|array',
            'is_active.*' => 'required|boolean',
            'is_silenced' => 'required|array',
            'is_silenced.*' => 'required|boolean',
        ]);

        $studentsIds = $request->input('is_active');
        $students = Student::whereIn('id', array_keys($studentsIds))->get();
        foreach ($students as $student) {
            $student->is_active = $studentsIds[$student->id];
            $student->is_silenced = $request->input('is_silenced')[$student->id];
            $student->save();
        }

        return redirect()->route('admin.bulk-edit-students.edit', ['type' => $type]);
    }
}

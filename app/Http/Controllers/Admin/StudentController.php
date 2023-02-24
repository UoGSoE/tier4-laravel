<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function show(Student $student)
    {
        $student->load(['notes' => fn ($query) => $query->orderByDesc('updated_at')]);

        return view('admin.student.show', [
            'student' => $student,
        ]);
    }

    public function update(Student $student, Request $request)
    {
        $request->validate([
            'forenames' => 'required',
            'surname' => 'required',
            'email' => 'required|email',
            'is_silenced' => 'required|boolean',
            'silenced_reason' => 'required_if:is_silenced,true',
            'is_active' => 'required|boolean',
            'new_note' => 'nullable|string|max:1024',
        ]);

        $student->update([
            'forenames' => $request->forenames,
            'surname' => $request->surname,
            'email' => $request->email,
            'is_silenced' => $request->is_silenced,
            'silenced_reason' => $request->silenced_reason,
            'is_active' => $request->is_active,
        ]);

        if ($request->filled('new_note')) {
            $student->notes()->create([
                'user_id' => auth()->id(),
                'body' => $request->new_note,
            ]);
        }

        return redirect()->route('admin.student.show', $student)->with('success', 'Student updated!');
    }
}

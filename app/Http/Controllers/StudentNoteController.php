<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Models\Student;
use App\Models\StudentNote;
use Illuminate\Http\Request;

class StudentNoteController extends Controller
{
    // note that the `store` functionality is in the StudentController as it's done as part of the general 'edit a student' workflow

    public function update(Request $request, StudentNote $note): RedirectResponse
    {
        $request->validate([
            'body' => 'required|max:1024',
        ]);

        $note->update([
            'body' => $request->body,
        ]);

        return redirect()->route('admin.student.show', $note->student);
    }

    public function destroy(StudentNote $note): RedirectResponse
    {
        $student = $note->student;
        $note->delete();

        return redirect()->route('admin.student.show', $student);
    }
}

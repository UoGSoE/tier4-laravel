<?php

namespace App\Exports;

use App\Models\Student;
use Ohffs\SimpleSpout\ExcelSheet;

class PhdStudents
{
    public function export(): string
    {
        $students = Student::phd()->orderBy('surname')->with(['supervisor', 'latestMeeting'])->get()->map(function ($student) {
            return [
                $student->username,
                $student->surname,
                $student->forenames,
                $student->email,
                $student->supervisor->username,
                $student->supervisor->surname,
                $student->supervisor->forenames,
                $student->supervisor->email,
                $student->latestMeeting?->meeting_at->format('d/m/Y'),
            ];
        });
        $students->prepend([
            'Student GUID',
            'Surname',
            'Forenames',
            'Email',
            'Supervisor GUID',
            'Supervisor Surname',
            'Supervisor Forenames',
            'Supervisor Email',
            'Last Meeting',
        ]);

        return (new ExcelSheet())->generate($students->toArray());
    }
}

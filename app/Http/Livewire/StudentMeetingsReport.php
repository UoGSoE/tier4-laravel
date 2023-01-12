<?php

namespace App\Http\Livewire;

use App\Models\Student;
use Livewire\Component;

class StudentMeetingsReport extends Component
{
    public $student;
    public $meetings;

    public function mount(Student $student)
    {
        $this->student = $student;
        $this->meetings = $student->meetings()->orderByDesc('meeting_at')->with('supervisor')->get();
    }

    public function render()
    {
        return view('livewire.student-meetings-report');
    }
}

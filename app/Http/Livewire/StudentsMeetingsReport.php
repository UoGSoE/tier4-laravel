<?php

namespace App\Http\Livewire;

use App\Models\Student;
use Livewire\Component;

class StudentsMeetingsReport extends Component
{
    public $type = Student::TYPE_PHD;

    public $filter = '';

    public $includeInactive = false;

    public $onlyOverdue = true;

    protected $queryString = ['type', 'filter', 'includeInactive' => ['except' => false], 'onlyOverdue' => ['except' => true]];

    public function mount(?string $type = null)
    {
        if ($type) {
            $this->type = $type;
        }
    }

    public function render()
    {
        return view('livewire.students-meetings-report', [
            'students' => $this->getStudents(),
        ]);
    }

    public function getStudents()
    {
        $optionName = $this->type === Student::TYPE_PHD ? 'phd_meeting_reminder_days' : 'postgrad_project_meeting_reminder_days';

        return Student::where('type', '=', $this->type)
            ->when(
                strlen(trim($this->filter)) > 2,
                fn ($query) => $query->where(
                    fn ($query) => $query->where('email', 'like', "%{$this->filter}%")
                                    ->orWhere('surname', 'like', "%{$this->filter}%")
                                    ->orWhere('forenames', 'like', "%{$this->filter}%")
                )
            )
            ->when(! $this->includeInactive, fn ($query) => $query->active())
            ->when($this->onlyOverdue, fn ($query) => $query->overdue(option($optionName, 28)))
            ->with(['latestMeeting', 'supervisor'])
            ->orderBy('surname')
            ->get();
    }
}

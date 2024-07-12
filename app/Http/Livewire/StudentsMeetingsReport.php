<?php

namespace App\Http\Livewire;

use App\Models\Student;
use Livewire\Component;
use Ohffs\SimpleSpout\ExcelSheet;

class StudentsMeetingsReport extends Component
{
    public $type = Student::TYPE_PHD;

    public $filter = '';

    public $includeInactive = false;

    public $onlyOverdue = true;

    public $sortField = 'surname';

    public $sortDirection = 'asc';

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

        $students = Student::where('type', '=', $this->type)
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
            ->with(['latestMeeting', 'supervisor', 'latestNote'])
            ->when(in_array($this->sortField, ['surname', 'forenames', 'username', 'email']), fn ($query) => $query->orderBy($this->sortField, $this->sortDirection))
            ->get();

        if ($this->sortField === 'latestMeeting') {
            return $this->sortDirection === 'asc' ? $students->sortBy(fn ($student) => $student->latestMeeting?->meeting_at) : $students->sortByDesc(fn ($student) => $student->latestMeeting?->meeting_at);
        }
        if ($this->sortField === 'supervisorName') {
            return $this->sortDirection === 'asc' ? $students->sortBy(fn ($student) => $student->supervisor?->surname) : $students->sortByDesc(fn ($student) => $student->supervisor?->surname);
        }

        return $students;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function exportExcel()
    {
        $filename = 'students-meetings-report-'.now()->format('Y-m-d').'.xlsx';

        $rows = [];
        $rows[] = ['Matric', 'Surname', 'Forenames', 'Supervisor', 'Last Meeting'];
        foreach ($this->getStudents() as $student) {
            $rows[] = [
                $student->username,
                $student->surname,
                $student->forenames,
                $student->supervisor?->fullName,
                $student->latestMeeting?->meeting_at->format('d/m/Y'),
            ];
        }

        $sheet = (new ExcelSheet())->generate($rows);

        return response()->download($sheet, $filename)->deleteFileAfterSend(true);
    }
}

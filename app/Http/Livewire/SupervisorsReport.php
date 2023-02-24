<?php

namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;

class SupervisorsReport extends Component
{
    public $filter = '';

    public function render()
    {
        return view('livewire.supervisors-report', [
            'supervisors' => $this->getSuperVisors(),
        ]);
    }

    public function getSuperVisors()
    {
        return User::with(['students.latestMeeting', 'students.latestNote'])
            ->when($this->filter, function ($query) {
                $query->where('forenames', 'like', "%{$this->filter}%")
                    ->orWhere('surname', 'like', "%{$this->filter}%");
            })
            ->orderBy('surname')
            ->get();
    }
}

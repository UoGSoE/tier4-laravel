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
        $query = User::with(['students.latestMeeting', 'students.latestNote'])
            ->orderBy('surname');

        if (trim($this->filter)) {
            $query = $this->applyFilter($query);
        }

        return $query->get();
    }

    protected function applyFilter($query)
    {
        return $query->where(function ($query) {
            $query->where('forenames', 'like', "%{$this->filter}%")
                ->orWhere('surname', 'like', "%{$this->filter}%");
        });
    }
}

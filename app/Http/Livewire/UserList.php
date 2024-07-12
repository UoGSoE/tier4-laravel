<?php

namespace App\Http\Livewire;

use App\Events\SomethingHappened;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;

class UserList extends Component
{
    public $username = '';

    public $surname = '';

    public $forenames = '';

    public $email = '';

    public $error = null;

    public function render()
    {
        return view('livewire.user-list', [
            'users' => User::admin()->orderBy('surname')->get(),
        ]);
    }

    public function lookupUser()
    {
        if (! $this->username) {
            return;
        }

        $existingUser = User::where('username', '=', $this->username)->first();
        if ($existingUser?->isAdmin()) {
            $this->error = 'User is already an admin';

            return;
        }

        $ldapUser = \Ldap::findUser($this->username);
        if (! $ldapUser) {
            $this->error = 'User not found';

            return;
        }

        $this->username = $ldapUser->username;
        $this->surname = $ldapUser->surname;
        $this->forenames = $ldapUser->forenames;
        $this->email = $ldapUser->email;
    }

    public function createUser()
    {
        $existingUser = User::where('username', '=', $this->username)->first();
        if ($existingUser) {
            $existingUser->is_admin = true;
            $existingUser->save();
            $this->reset();

            return;
        }

        $this->validate([
            'username' => 'required|unique:users',
            'surname' => 'required',
            'forenames' => 'required',
            'email' => 'required|email|unique:users',
        ]);

        User::create([
            'username' => $this->username,
            'surname' => $this->surname,
            'forenames' => $this->forenames,
            'email' => $this->email,
            'is_staff' => true,
            'is_admin' => true,
            'password' => bcrypt(Str::random(64)),
        ]);

        SomethingHappened::dispatch(auth()->user()->full_name.' created a new admin user '.$this->username);

        $this->reset();
    }

    public function demoteUser($userId)
    {
        if (auth()->id() == $userId) {
            return;
        }
        $demotedUser = User::findOrFail($userId);
        $demotedUser->update(['is_admin' => false]);

        SomethingHappened::dispatch(auth()->user()->full_name.' demoted a user '.$demotedUser->username);

        $this->reset();
    }

    public function toggleWantsEmail($userId, $emailType)
    {
        $user = User::findOrFail($userId);
        $user->update([
            $emailType => ! $user->$emailType,
        ]);

        SomethingHappened::dispatch(auth()->user()->full_name.' toggled '.$emailType.' for '.$user->username);
    }
}

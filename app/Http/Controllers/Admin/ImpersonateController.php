<?php

namespace App\Http\Controllers\Admin;

use App\Events\SomethingHappened;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ImpersonateController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        SomethingHappened::dispatch(auth()->user()->full_name . ' started impersonating ' . $user->full_name);

        auth()->user()->impersonate($user);

        return redirect()->route('home');
    }

    public function destroy(): RedirectResponse
    {
        auth()->user()->leaveImpersonation();

        SomethingHappened::dispatch(auth()->user()->full_name . ' stopped impersonating');

        return redirect()->route('home');
    }
}

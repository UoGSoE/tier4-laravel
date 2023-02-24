<?php

namespace App\Http\Controllers\Admin;

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

        auth()->user()->impersonate($user);

        return redirect()->route('home');
    }

    public function destroy(): RedirectResponse
    {
        auth()->user()->leaveImpersonation();

        return redirect()->route('home');
    }
}

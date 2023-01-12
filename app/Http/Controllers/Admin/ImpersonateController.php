<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ImpersonateController extends Controller
{
    public function store(User $user)
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('home');
        }

        auth()->user()->impersonate($user);

        return redirect()->route('home');
    }

    public function destroy()
    {
        auth()->user()->leaveImpersonation();

        return redirect()->route('home');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function edit(): View
    {
        return view('admin.admins.edit');
    }
}

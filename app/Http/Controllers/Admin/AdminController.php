<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function edit()
    {
        return view('admin.admins.edit');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ActivityController extends Controller
{
    public function index(): View
    {
        return view('admin.activity.index', [
            'activity' => Activity::latest()->paginate(100),
        ]);
    }

}

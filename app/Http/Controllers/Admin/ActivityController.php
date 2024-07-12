<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        return view('admin.activity.index', [
            'activity' => Activity::latest()->paginate(100),
        ]);
    }
}

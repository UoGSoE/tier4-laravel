<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'students' => auth()->user()->students()->active()->with('latestMeeting')->orderBy('surname')->get(),
        ]);
    }
}

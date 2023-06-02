<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'phdStudents' => auth()->user()->phdStudents()->active()->with('latestMeeting')->orderBy('surname')->get(),
            'projectStudents' => auth()->user()->postgradProjectStudents()->active()->with('latestMeeting')->orderBy('surname')->get(),
        ]);
    }
}

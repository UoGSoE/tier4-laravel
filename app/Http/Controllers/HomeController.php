<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'students' => auth()->user()->students()->active()->with('latestMeeting')->orderBy('surname')->get(),
        ]);
    }
}

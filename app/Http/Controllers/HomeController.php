<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'students' => auth()->user()->students()->active()->with('latestMeeting')->orderBy('surname')->get(),
        ]);
    }
}

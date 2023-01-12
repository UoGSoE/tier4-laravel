<?php

namespace App\Http\Controllers\Imports;

use App\Models\User;
use App\Models\Student;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Ohffs\SimpleSpout\ExcelSheet;
use Illuminate\Support\MessageBag;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PhdsController extends Controller
{
    public function create()
    {
        return view('admin.import.phd');
    }

    public function store(Request $request)
    {
        $request->validate([
            'sheet' => 'required|file',
        ]);

        $errors = new MessageBag();
        $rows = (new ExcelSheet())->import($request->file('sheet'));

        foreach ($rows as $index => $row) {
            $matric = $row[0] ?? '';
            $surname = $row[1] ?? '';
            $forenames = $row[2] ?? '';
            $email = strtolower(trim($row[3] ?? ''));
            $supervisorUsername = $row[4] ?? '';
            $supervisorSurname = $row[5] ?? '';
            $supervisorForenames = $row[6] ?? '';
            $supervisorEmail = strtolower(trim($row[7] ?? ''));

            $validator = Validator::make([
                'matric' => $matric,
                'surname' => $surname,
                'forenames' => $forenames,
                'email' => $email,
                'supervisorUsername' => $supervisorUsername,
                'supervisorSurname' => $supervisorSurname,
                'supervisorForenames' => $supervisorForenames,
                'supervisorEmail' => $supervisorEmail,
            ], [
                'matric' => 'required|string',
                'surname' => 'required|string',
                'forenames' => 'required|string',
                'email' => 'required|email',
                'supervisorUsername' => 'required|string',
                'supervisorSurname' => 'required|string',
                'supervisorForenames' => 'required|string',
                'supervisorEmail' => 'required|email',
            ]);

            if ($validator->fails()) {
                if ($index === 0) {
                    continue;  // assume it's the header row
                }
                $errors->add('Row ' . $index + 1 . ':', implode(', ', $validator->errors()->all()));
                continue;
            }

            $supervisor = User::where('username', '=', $supervisorUsername)->first();
            if (! $supervisor) {
                $supervisor = User::create([
                    'username' => $supervisorUsername,
                    'surname' => $supervisorSurname,
                    'forenames' => $supervisorForenames,
                    'email' => $supervisorEmail,
                    'password' => bcrypt(Str::random(64)),
                    'is_staff' => true,
                ]);
            }

            $student = Student::where('email', '=', $email)->first();
            if (! $student) {
                $student = Student::make([
                    'username' => strtolower($matric . $surname[0]),
                    'surname' => $surname,
                    'forenames' => $forenames,
                    'email' => $email,
                    'type' => Student::TYPE_PHD,
                ]);
            }
            $student->supervisor_id = $supervisor->id;
            $student->save();
        }

        if ($errors->count() > 0) {
            return redirect()->route('admin.import.phds.create')->withErrors($errors);
        }
        return redirect()->route('admin.import.phds.create')->with('success', 'PhD students imported successfully.');
    }
}

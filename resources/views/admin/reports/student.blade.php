@extends('layouts.app')
@section('content')

@livewire('student-meetings-report', ['student' => $student])

@endsection

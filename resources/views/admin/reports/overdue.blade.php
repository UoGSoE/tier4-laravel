@extends('layouts.app')
@section('content')

@livewire('students-meetings-report', ['type' => $type])

@endsection

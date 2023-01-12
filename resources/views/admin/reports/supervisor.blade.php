@extends('layouts.app')
@section('content')

<div class="level">
    <div class="level-left">
        <div class="level-item">
            <h3 class="title is-3">
                All meetings with {{ $supervisor->full_name }} (<a href="mailto:{{ $supervisor->email }}">{{ $supervisor->email }}</a>)
            </h3>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <a class="button" href="{{ route('admin.gdpr.staff.export', $supervisor) }}">GDPR Export</a>
        </div>
        <div class="level-item">
            <a href="{{ route('impersonate', $supervisor->id) }}" class="button is-pulled-right">Impersonate</a>
        </div>
    </div>
</div>

<table class="table is-fullwidth is-striped is-hoverable">
    <thead>
        <tr>
            <th>Student</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($meetings as $meeting)
            <tr>
                <td>
                    <a href="{{ route('reports.student', ['student' => $meeting->student]) }}">
                        {{ $meeting->student->full_name }}
                    </a>
                </td>
                <td>{{ $meeting->meeting_at?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

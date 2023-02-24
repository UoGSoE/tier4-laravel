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
                    @if ($meeting->student->isSilenced())
                        <span class="tag is-small" title="{{ $meeting->student->silenced_reason }}">
                            Silenced
                        </span>
                    @endif
                    @if ($meeting->student->latestNote)
                        <span class="icon" x-data x-tooltip="{{ $meeting->student->latestNote->updated_at->format('d/m/Y') }} - {{ $meeting->student->latestNote->body }}">
                            <i class="has-text-dark" >
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
                                    <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                                    <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9.06 9.06 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.437 10.437 0 0 1-.524 2.318l-.003.011a10.722 10.722 0 0 1-.244.637c-.079.186.074.394.273.362a21.673 21.673 0 0 0 .693-.125zm.8-3.108a1 1 0 0 0-.287-.801C1.618 10.83 1 9.468 1 8c0-3.192 3.004-6 7-6s7 2.808 7 6c0 3.193-3.004 6-7 6a8.06 8.06 0 0 1-2.088-.272 1 1 0 0 0-.711.074c-.387.196-1.24.57-2.634.893a10.97 10.97 0 0 0 .398-2z"/>
                                </svg>
                            </i>
                        </span>
                    @endif
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

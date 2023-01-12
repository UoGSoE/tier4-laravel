@extends('layouts.app')
@section('content')

<div class="box">
    <p class="is-hidden-mobile">
        This system generates evidence of attendance and confirms that postgraduate students are active in their research.
        You are required to accurately enter information regarding meetings you have with your PGR students.
        This information will be monitored for block entry information and occurrences will be passed to the School Management Board.
        Thank you for your time and patience, this system is the minimum requirement for Tier 4 compliance.
    </p>
    <br class="is-hidden-mobile" />
    <p>
        Please update the dates next to any students you've seen recently then press the "Submit" button
        at the bottom of the page.
    </p>
</div>
<form method="POST" action="{{ route('meetings.store') }}">
    @csrf
    <div class="table-container">
        <table class="table is-fullwidth is-striped is-hoverable">
            <thead>
                <tr>
                    <th class="is-hidden-mobile">Matric</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Last Seen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students as $student)
                    <tr
                        @class([
                            'has-background-danger-light' => $student->isOverdue(),
                        ])
                        title="{{ $student->full_name }} ({{ $student->username }})"
                    >
                        <td class="is-hidden-mobile">{{ $student->username }}</td>
                        <td>{{ $student->forenames }}</td>
                        <td>{{ $student->surname }}</td>
                        <td>
                            <div
                                class="field"
                            >
                                <input type="hidden" name="meetings[{{ $student->id }}][student_id]" value="{{ $student->id }}">
                                <div class="control">
                                    <input
                                        x-data
                                        x-on:change="$dispatch('input', $event.target.value)"
                                        x-init="flatpickr($refs.pickr, { dateFormat: 'd/m/Y', defaultDate: '{{ $student->latestMeeting?->meeting_at->format('d/m/Y') }}' })"
                                        x-ref="pickr"
                                        name="meetings[{{ $student->id }}][date]"
                                        class="input"
                                        type="text"
                                        placeholder="dd/mm/yyyy"
                                        value="{{ $student->latestMeeting?->meeting_at->format('d/m/Y') }}"
                                    >
                                </div>
                            </div>

                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td class="is-hidden-mobile"></td>
                    <td class="is-hidden-mobile"></td>
                    <td class="is-hidden-mobile"></td>
                    <td>
                        <div class="field is-pulled-right">
                            <div class="control">
                                <button class="button is-primary">Submit</button>
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</form>
@endsection

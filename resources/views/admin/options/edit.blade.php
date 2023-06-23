@extends('layouts.app')
@section('content')

<h3 class="title is-3">Edit Options</h3>

<div class="box">
    <form action="{{ route('admin.options.update') }}" method="POST">
        @csrf
        <div class="columns">
            <div class="column">
                <div class="field">
                    <div class="control">
                        <label class="label">PhD Meeting Frequency (in days)</label>
                        <input class="input" type="number" name="phd_meeting_reminder_days" value="{{ option('phd_meeting_reminder_days') }}" min="1">
                    </div>
                </div>
            </div>
        </div>

        <div class="columns">
            <div class="column">
                <label class="label">Postgrad Project Email Dates (dd/mm/yyyy)</label>
                @foreach (range(1, 5) as $i)
                    <div class="field">
                        <div class="control">
                            <input
                                x-data
                                x-on:change="$dispatch('input', $event.target.value)"
                                x-init="flatpickr($refs.pickr, { dateFormat: 'd/m/Y', defaultDate: '{{ option("postgrad_project_email_date_{$i}") ? \Carbon\Carbon::createFromFormat('Y-m-d', option("postgrad_project_email_date_{$i}"))->format('d/m/Y') : "" }}' })"
                                x-ref="pickr"
                                name="postgrad_project_email_date_{{ $i }}"
                                class="input"
                                type="text"
                                placeholder="dd/mm/yyyy"
                                value="{{ option("postgrad_project_email_date_{$i}") ? \Carbon\Carbon::createFromFormat('Y-m-d', option("postgrad_project_email_date_{$i}"))->format('d/m/Y') : "" }}"
                            >
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <hr>

        <div class="field">
            <div class="control">
                <button class="button is-info">Save</button>
            </div>
        </div>
    </form>
</div>
@endsection

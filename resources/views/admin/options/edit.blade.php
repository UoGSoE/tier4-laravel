@extends('layouts.app')
@section('content')

<h3 class="title is-3">Edit Options</h3>

<div class="box">
    <form action="{{ route('admin.options.update') }}" method="POST">
        @csrf
        <div class="field">
            <div class="control">
                <label class="label">PhD Meeting Frequency (in days)</label>
                <input class="input" type="number" name="phd_meeting_reminder_days" value="{{ option('phd_meeting_reminder_days') }}" min="1">
            </div>
        </div>

        <div class="field">
            <div class="control">
                <label class="label">Postgrad Project Meeting Frequency (in days)</label>
                <input class="input" type="number" name="postgrad_project_meeting_reminder_days" value="{{ option('postgrad_project_meeting_reminder_days') }}" min="1">
            </div>
        </div>

        <label class="label">Postgrad Project Start</label>
        <div class="field is-grouped">
            <div class="control">
              <div class="select">
                <select name="postgrad_project_start_month">
                    @foreach ($months as $index => $name)
                        <option value="{{ $index }}" @selected(option('postgrad_project_start_month') == $index)>{{ $name }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="control">
                <input class="input" type="number" name="postgrad_project_start_day" value="{{ option('postgrad_project_start_day') }}" min="1" max="31">
            </div>
        </div>

        <label class="label">Postgrad Project End</label>
        <div class="field is-grouped">
            <div class="control">
              <div class="select">
                <select name="postgrad_project_end_month">
                    @foreach ($months as $index => $name)
                        <option value="{{ $index }}" @selected(option('postgrad_project_end_month') == $index)>{{ $name }}</option>
                    @endforeach
                </select>
              </div>
            </div>
            <div class="control">
                <input class="input" type="number" name="postgrad_project_end_day" value="{{ option('postgrad_project_end_day') }}" min="1" max="31">
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

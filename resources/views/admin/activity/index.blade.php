@extends('layouts.app')
@section('content')

<h3 class="title is-3">
    Activity Log
</h3>

{{ $activity->links('vendor.pagination.bulma') }}

<table class="table is-fullwidth is-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Event</th>
        </tr>
    </thead>
    <tbody>
        @foreach($activity as $event)
            <tr>
                <td>{{ $event->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $event->message }}</td>
            </tr>
        @endforeach
</table>

{{ $activity->links('vendor.pagination.bulma') }}

@endsection

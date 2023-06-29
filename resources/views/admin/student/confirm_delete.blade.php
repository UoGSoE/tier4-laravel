@extends('layouts.app')
@section('content')

<h3 class="title is-3">Really delete student {{ $student->full_name }} ({{ $student->username }})?</h3>
<p class="subtitle">
    Are you <em>sure</em> you want to delete this student - it will remove all sign of them from the system?
    There is <b><em>no way</em></b> to undo this action.
</p>

<hr />

<form method="POST" action="{{ route('admin.student.delete', $student) }}">
    @csrf
    <div class="field is-grouped">
        <div class="control">
            <button class="button is-danger">Yes, delete them and all their associated records</button>
        </div>
        <div class="control">
            <a href="{{ route('admin.student.show', $student) }}" class="button is-text">No, take me back</a>
        </div>
    </div>
</form>

@endsection

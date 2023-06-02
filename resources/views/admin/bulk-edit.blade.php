@extends('layouts.app')
@section('content')

<h3 class="title is-3">Bulk Edit Active {{ $formattedType }} Students</h3>
<p class="subtitle">(These are all the students who have had a meeting in the past six months)</p>

<form action="" method="post">
    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Student</th>
                <th>Supervisor</th>
                <th>
                    <div class="level">
                        <div class="level-left">
                            <div class="level-item">Active?&nbsp;<button id="toggle-active" class="button is-small">Toggle All</button></div>
                        </div>
                    </div>
                </th>
                <th>
                    <div class="level">
                        <div class="level-left">
                            <div class="level-item">Silenced?&nbsp;<button id="toggle-silenced" class="button is-small">Toggle All</button></div>
                        </div>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
            <tr>
                <td>
                    <a href="{{ route('admin.student.show', $student) }}"><span class="is-family-monospace">({{ $student->username }})</span> {{ $student->full_name }}</a>
                </td>
                <td>
                    @if($student->supervisor)
                        <a href="{{ route('reports.supervisor', $student->supervisor) }}">{{ $student->supervisor->full_name }}</a>
                    @endif
                </td>
                <td>
                    <div class="field">
                        <div class="control">
                            <input type="hidden" name="is_active[{{ $student->id}}]" value="0">
                            <label class="checkbox">
                                <input type="checkbox" @checked($student->isActive()) name="is_active[{{ $student->id }}]" value="1">
                            </label>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="field">
                        <div class="control">
                            <input type="hidden" name="is_silenced[{{ $student->id}}]" value="0">
                            <label class="checkbox">
                                <input type="checkbox" @checked($student->isSilenced()) name="is_silenced[{{ $student->id }}]" value="1">
                            </label>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
    </table>
    <hr>
    <div class="level">
        <div class="level-left"></div>
        <div class="level-right">
            <div class="field">
                <div class="control">
                    <button class="button is-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.querySelector('#toggle-active').addEventListener('click', (e) => {
        toggleCheckboxes(e, 'is_active');
    });

    document.querySelector('#toggle-silenced').addEventListener('click', (e) => {
        toggleCheckboxes(e, 'is_silenced');
    });

    function toggleCheckboxes(e, name) {
        e.stopPropagation();
        e.preventDefault();
        const checkboxes = document.querySelectorAll(`input[type="checkbox"][name^="${name}"]`);
        checkboxes.forEach(checkbox => {
            checkbox.checked = !checkbox.checked;
        });
    }
</script>
@endpush

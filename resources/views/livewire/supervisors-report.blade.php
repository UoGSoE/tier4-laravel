<div>
    <h3 class="title is-3">All the latest meetings</h3>
    <div class="field">
        <label for="search" class="label">Filter Supervisors</label>
        <div class="control">
            <input type="text" wire:model="filter" class="input" id="search">
        </div>
    </div>
    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Supervisor</th>
                <th>Student</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($supervisors as $supervisor)
                @foreach ($supervisor->students as $student)
                    @if ($student->isActive())
                        <tr>
                            <td>
                                <a href="{{ route('reports.supervisor', ['supervisor' => $supervisor]) }}">
                                    {{ $supervisor->full_name }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('reports.student', ['student' => $student]) }}">
                                    @if ($student->isSilenced())
                                        <span class="tag is-small" title="{{ $student->silenced_reason }}">
                                            Silenced
                                        </span>
                                    @endif
                                    {{ $student->full_name }}
                                </a>
                            </td>
                            <td>{{ $student->latestMeeting?->meeting_at?->format('d/m/Y') }}</td>
                        </tr>
                    @endif
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>

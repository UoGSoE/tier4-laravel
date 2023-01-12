<div>
    <h3 class="title is-3">Overdue Students</h3>
    <div class="box">
        <div class="columns">
            <div class="column">
                <div class="field">
                    <label for="type" class="label">Student Type</label>
                    <div class="control">
                        <div class="select">
                            <select wire:model="type" id="type">
                                <option value="{{ \App\Models\Student::TYPE_PHD }}">Phd</option>
                                <option value="{{ \App\Models\Student::TYPE_POSTGRAD_PROJECT }}">Postgrad Project</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="field">
                    <label for="onlyoverdue" class="label">Only show overdue students?</label>
                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" wire:model="onlyOverdue" id="onlyoverdue" value="1">
                            Yes
                        </label>
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="field">
                    <label for="includeinactive" class="label">Show inactive students?</label>
                    <div class="control">
                        <label class="checkbox">
                            <input type="checkbox" wire:model="includeInactive" id="includeinactive">
                            Yes
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="field">
            <label for="search" class="label">Search</label>
            <div class="control">
                <input type="text" wire:model="filter" class="input" id="search">
            </div>
        </div>
    </div>

    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Surname</th>
                <th>Forenames</th>
                <th>Email</th>
                <th>Supervisor</th>
                <th>Last Meeting</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
                <tr>
                    <td>
                        @if ($student->isSilenced())
                            <span class="tag is-small" title="{{ $student->silenced_reason }}">
                                Silenced
                            </span>
                        @endif
                        <a href="{{ route('reports.student', ['student' => $student]) }}">
                            {{ $student->surname }}
                        </a>
                    </td>
                    <td>{{ $student->forenames }}</td>
                    <td>
                        <a href="mailto:{{ $student->email }}">
                            {{ $student->email }}
                        </a>
                    </td>
                    <td>
                        <a href="{{ route('reports.supervisor', ['supervisor' => $student->supervisor]) }}">
                            {{ $student->supervisor->full_name }}
                        </a>
                    </td>
                    <td>{{ $student->latestMeeting?->meeting_at->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

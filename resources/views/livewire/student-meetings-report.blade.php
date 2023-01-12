<div>
    <div class="level">
        <div class="level-left">
            <div class="level-item">
                <h3 class="title is-3">
                    @if ($student->isSilenced())
                        <span class="tag is-medium" title="{{ $student->silenced_reason }}">Silenced</span>
                    @endif
                    All meetings for {{ $student->full_name }} (<a href="mailto:{{ $student->email }}">{{ $student->email }}</a>)
                </h3>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <a class="button" href="{{ route('admin.gdpr.student.export', $student->id) }}">GDPR Export</a>
            </div>
            <div class="level-item">
                <a class="button" href="{{ route('admin.student.show', $student) }}">Edit</a>
            </div>
        </div>

    </div>

    <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
            <tr>
                <th>Supervisor</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($meetings as $meeting)
                <tr>
                    <td>
                        <a href="{{ route('reports.supervisor', ['supervisor' => $meeting->supervisor]) }}">
                            {{ $meeting->supervisor->full_name }}
                        </a>
                    </td>
                    <td>{{ $meeting->meeting_at?->format('d/m/Y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

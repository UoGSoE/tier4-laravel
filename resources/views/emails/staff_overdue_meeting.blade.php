<x-mail::message>
# Tier4 - Overdue meetings

@if ($overdueStudents->count() > 1)
    You have overdue meetings with the following students:
@else
    You have an overdue meeting with the following student:
@endif

@foreach ($overdueStudents as $student)
    * {{ $student->full_name }} ({{ $student->username }})
@endforeach

<x-mail::button :url="route('home')">
Log in to Tier4
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

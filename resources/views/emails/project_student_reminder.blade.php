<x-mail::message>
# Reminder: Tier4 Project Students

This is an automatic reminder to update your last contact date for your project students.

<x-mail::button url="{{ route('home') }}">
Log Into Tier4
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

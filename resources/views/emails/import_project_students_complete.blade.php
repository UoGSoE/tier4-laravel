<x-mail::message>
# The import has completed.

@if (count($errors) > 0)
## The following errors were encountered:
@foreach ($errors as $error)
- {{ $error }}
@endforeach
@endif

<x-mail::button url="{{ route('home') }}">
Go to Tier4
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

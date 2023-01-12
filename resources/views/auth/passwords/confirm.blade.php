@extends('layouts.app')

@section('content')
<article class="message is-warning">
    <div class="message-header">
        <p>Please confirm your password before continuing</p>
    </div>
    <div class="message-body">
        Please re-enter your password below. This is just to make extra sure before all the emails are sent out.
        We won't ask for your password again for a few hours.
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <div class="field">
                <label class="label" for="password">Password</label>
                <div class="control">
                    <input class="input" type="password" name="password" required>
                    @error('password')
                        <span class="has-text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="field">
                <div class="control"><button class="button">Confirm</button></div>
            </div>
        </form>
    </div>
</article>

@endsection
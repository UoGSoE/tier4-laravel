@extends('layouts.app')
@section('content')
<div class="loginbox">
            <div class="columns is-centered">

                <div class="column is-one-third box">

                    <div class="shadow-lg login-form">
                        <div class="login-header has-text-centered">
                            <h1 class="title is-1 mb-4">Tier4</h1>
                        </div>
                        @error('authentication')
                        <article style="background: #FF7777; color: white; text-align: center;" class="p-8" v-show="errorMessage">
                            <b>{{ $message }}</b>
                        </article>
                        @enderror

                        <form key="2" method="POST" action="{{ route('auth.do_login') }}" class=" p-8 ">
                            @csrf
                            <div class="field">
                                <label class="label">Username</label>
                                <p class="control">
                                    <input class="input" type="text" name="username" required autofocus>
                                </p>
                                @error('username')
                                <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </div>
                                <div class="field">
                                    <label class="label">Password</label>
                                    <p class="control">
                                        <input class="input" type="password" name="password" required>
                                    </p>
                                    @error('password')
                                    <p class="help is-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            <hr />
                            <div class="field">
                                <button class="button is-info is-fullwidth">Log In</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- loginbox -->
    </div>
@endsection

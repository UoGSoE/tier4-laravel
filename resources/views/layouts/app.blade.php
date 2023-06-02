<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tier4</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
        @auth
            @include('layouts.navbar')
        @endauth
        <div class="section">
            <div class="container">
                @if (session('success'))
                    <div class="notification is-success has-text-weight-semibold">
                        {{ session('success') }}
                    </div>
                @endif
                @if (count($errors) > 0)
                    <div class="notification is-warning">
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
        @livewireScripts
    </body>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
            if ($navbarBurgers.length > 0) {
                $navbarBurgers.forEach( el => {
                    el.addEventListener('click', () => {
                        const target = el.dataset.target;
                        const $target = document.getElementById(target);
                        el.classList.toggle('is-active');
                        $target.classList.toggle('is-active');
                    });
                });
            }
            // add the class 'is-danger' to the navbar after 1hr 50 minutes has passed
            setTimeout(() => {
                document.getElementById('navbar').classList.add('is-danger');
            }, 7000000);
        });
    </script>
    @stack('scripts')
</html>

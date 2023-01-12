<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body>
        <div class="section">
            <div class="columns">
                <div class="column is-three-fifths is-offset-one-fifth">
                    <div class="has-text-centered">
                        <img src="/UoG_colour.png" alt="University of Glasgow Logo" style="height: 10vh; object-fit: contain;">
                    </div>
                    @yield('content')
                </div>
            </div>
        </div>
    </body>
</html>

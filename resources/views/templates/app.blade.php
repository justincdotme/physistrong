<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="/css/app.css">
        <title>{{ $title ?? 'Physistrong' }}</title>
    </head>
    <body>
        <div class="container-fluid">
            <div id="app">
                <ul class="nav">
                    <li class="nav-item">
                        <router-link
                                class="nav-link"
                                active-class="active"
                                :to="{ name: 'home' }">Workouts</router-link>
                    </li>
                </ul>
                <router-view></router-view>
            </div>
        </div>
        <script src="/js/manifest.js"></script>
        <script src="/js/vendor.js"></script>
        <script src="{{ mix('/js/app.js') }}"></script>
    </body>
</html>
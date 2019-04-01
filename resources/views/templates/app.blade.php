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
        <div id="app">
            <nav class="navbar navbar-expand-md navbar-dark bg-dark">
                <div class="navbar-collapse collapse w-100 order-1 order-md-0 dual-collapse2">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item">
                            <router-link
                                    class="nav-link"
                                    active-class="active"
                                    :to="{ name: 'home' }">Home</router-link>
                        </li>
                        <li class="nav-item">
                            <router-link
                                    class="nav-link"
                                    active-class="active"
                                    :to="{ name: 'workouts' }">Workouts</router-link>
                        </li>
                    </ul>
                </div>
                <div class="mx-auto order-0">
                    <a class="navbar-brand mx-auto" href="#">Physistrong</a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".dual-collapse2">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                <div class="navbar-collapse collapse w-100 order-3 dual-collapse2">
                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item pull-right">
                            <login-link></login-link>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="container-fluid">
                <router-view></router-view>
            </div>

        </div>
        <script src="/js/manifest.js"></script>
        <script src="/js/vendor.js"></script>
        <script src="/js/app.js"></script>
        <script>
            var ziggy = window.ziggy || {};
            ziggy.baseUrl = "{{ $baseUrl }}";
            ziggy.baseDomain = "{{ $baseDomain }}";
            ziggy.baseProtocol = "{{ $baseProtocol }}";
            ziggy.basePort = "{{ $basePort }}";
        </script>
    </body>
</html>
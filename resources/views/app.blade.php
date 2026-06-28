<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Physistrong</title>
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('physistrong-theme')
                    var dark =
                        stored === 'dark'
                            ? true
                            : stored === 'light'
                              ? false
                              : window.matchMedia('(prefers-color-scheme: dark)').matches
                    document.documentElement.classList.toggle('dark', dark)
                } catch (e) {}
            })()
        </script>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>

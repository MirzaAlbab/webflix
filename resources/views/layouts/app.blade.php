<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Codeflix</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fontawesome-free-6.6.0-web/css/all.css') }}">
    @vite('resources/js/app.js')
    @stack('styles')
    @if(\App\Support\DemoMode::enabled())
        <style>
            .category-title { margin-top: 10rem; }
            .breadcrumb { margin-top: 10rem; }
            .jumbotron { margin-top: 10rem; }
        </style>
    @endif
</head>

<body>

    <x-navbar />
    @if(\App\Support\DemoMode::enabled())
        <div style="position:fixed;top:76px;left:0;right:0;z-index:1000"><x-demo-banner /></div>
    @endif

    @yield('content')

    <footer>
        <div class="text-center text-white">
            <p class="footer-title">&copy;
                <script>
                    document.write(new Date().getFullYear());
                </script> CODEPOLITAN. All rights reserved.
            </p>
        </div>
    </footer>
    <script src="{{ asset('assets/js/bootstrap.bundle.js') }}"></script>
    @stack('scripts')
</body>

</html>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        {{ config('app.name') }} | @yield('title')
    </title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link href="{{ mix('/cms-assets/css/app.css') }}" rel="stylesheet">
    <!-- KStock modern theme overlay -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('cms-assets/css/modern.css') }}?v=21" rel="stylesheet">
    @stack('css')
    <script src="{{ mix('/cms-assets/js/app.js') }}"></script>
    <script src="{{ mix('/cms-assets/js/plugins.js') }}"></script>
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    @stack('scriptTop')
</head>
@guest
    <body class="hold-transition login-page">
        <!-- flashing message -->
        @include('bkstar123_flashing::flashing')
        @yield('content')
        @stack('scriptBottom')
    </body>
@else
    <body class="hold-transition sidebar-mini layout-fixed">
        <div class="wrapper" id="app">
            <!-- NavBar -->
            @include('cms.layouts.components.navbar')
            <!-- SideBar -->
            @include('cms.layouts.components.sidebar')
            <!-- Contents -->
            @include('cms.layouts.components.contents')
            <!-- Footer -->
            @include('cms.layouts.components.footer')
        </div><!-- ./wrapper -->
        <!-- flashing message -->
        @include('bkstar123_flashing::flashing')
        <script type="text/javascript">
            Echo.private('user-' + {{ auth()->user()->id }})
                .listen('.a.job.failed', (data) => {
                    $.notify(data.error, {
                        position: "right bottom",
                        className: "error",
                        clickToHide: true,
                        autoHide: false,
                    })
                });
            Echo.private('user-' + {{ auth()->user()->id }})
                .listen('.pull.financial.statement.completed', (data) => {
                    $.notify('KStock has pulled the requested financial statement', {
                        position: "right bottom",
                        className: "success",
                        clickToHide: true,
                        autoHide: false,
                    })
                });
            Echo.private('user-' + {{ auth()->user()->id }})
                .listen('.analyze.financial.statement.completed', (data) => {
                    $.notify('KStock has analyzed the requested financial statement', {
                        position: "right bottom",
                        className: "success",
                        clickToHide: true,
                        autoHide: false,
                    })
                });
        </script>
        @stack('scriptBottom')
    </body>
@endguest
</html>

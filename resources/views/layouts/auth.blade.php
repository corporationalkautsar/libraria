<!DOCTYPE html>
<html lang="id" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a070c">
    <title>@yield('title', 'Login') — {{ $library['library_name'] }}</title>
    <link rel="icon" href="{{ asset('vendor/icons/library-big.svg') }}">
    <script>
        document.documentElement.classList.remove('no-js');
        document.documentElement.classList.add('js');
        try {
            if (sessionStorage.getItem('libraria-lamp') === 'on') {
                document.documentElement.classList.add('lamp-restored');
            }
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="{{ asset('vendor/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="@yield('body_class', 'auth-page')">
@yield('body')
@stack('scripts')
</body>
</html>

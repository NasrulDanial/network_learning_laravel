<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $pageTitle ?? 'NetLearn' }}</title>

    <script>
        if (localStorage.getItem('netlearn-theme') === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >
</head>
<body class="auth-body">

    @yield('content')

    <script src="{{ asset('js/theme.js') }}"></script>
</body>
</html>
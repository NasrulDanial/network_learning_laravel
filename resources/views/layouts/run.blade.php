<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Mission Run' }} · NetLearn</title>

    <script>
        (function () {
            var saved = null;
            try { saved = localStorage.getItem('netlearn-theme'); } catch (e) {}
            if (saved === 'light' || (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/netlearn-run.css') }}">
</head>
<body class="run-body">
    @yield('content')

    <script>window.NETLEARN_SOUND_DEFAULT = @json((bool) config('netlearn.sound_default_on'));</script>
    <script src="{{ asset('js/run/netfx.js') }}"></script>
    @yield('scripts')
</body>
</html>

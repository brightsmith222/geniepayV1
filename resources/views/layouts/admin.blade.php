<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geneipay Dashboard</title>
    <link rel="stylesheet" href="{{ assets('css/admin.css') }}">
    {{-- <script src="{{ asset('js/admin.js') }}" defer></script> --}}
</head>
<body>
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Main Content -->
    <div class="main-content">
        @include('partials.navbar')
        <div class="container">
            @yield('content')
        </div>
    </div>
</body>
</html>

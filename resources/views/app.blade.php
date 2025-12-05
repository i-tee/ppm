<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $appUrl }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:image" content="{{ $appUrl }}{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ $appUrl }}">
    <meta property="twitter:title" content="{{ $ogTitle }}">
    <meta property="twitter:description" content="{{ $ogDesc }}">
    <meta property="twitter:image" content="{{ $ogImage }}">

    {{-- favicon / PWA --}}
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="{{ $myWebSite }}">
    <link rel="manifest" href="/site.webmanifest">

    @vite(['resources/js/app.js', 'resources/css/app.css'])
</head>

<body>
    <div id="app"></div>
</body>

</html>
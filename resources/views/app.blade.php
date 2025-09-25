<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- PWA Meta Tags -->
        <meta name="application-name" content="{{ config('app.name') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
        <meta name="format-detection" content="telephone=no">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="msapplication-config" content="/browserconfig.xml">
        <meta name="msapplication-TileColor" content="#3b82f6">
        <meta name="msapplication-tap-highlight" content="no">
        <meta name="theme-color" content="#3b82f6">

        <!-- PWA Icons -->
        <link rel="apple-touch-icon" href="/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/icons/icon-152x152.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/icons/icon-192x192.png">
        <link rel="apple-touch-icon" sizes="167x167" href="/icons/icon-192x192.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16x16.png">
        <link rel="manifest" href="/manifest.json">
        <link rel="mask-icon" href="/icons/safari-pinned-tab.svg" color="#3b82f6">
        <link rel="shortcut icon" href="/favicon.ico">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- PWA Install Prompt - Temporarily Disabled -->
        <!-- <div id="pwa-install-prompt" class="hidden fixed bottom-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50" style="display:none">...</div> -->

        <!-- Service Worker Registration - Temporarily Disabled -->
        <script>
            // Temporarily disable service worker to troubleshoot loading issues
            // if ('serviceWorker' in navigator) {
            //     window.addEventListener('load', function() {
            //         navigator.serviceWorker.register('/sw.js')
            //             .then(function(registration) {
            //                 console.log('ServiceWorker registration successful');
            //             })
            //             .catch(function(err) {
            //                 console.log('ServiceWorker registration failed: ', err);
            //             });
            //     });
            // }

            // PWA Install Prompt - Temporarily Disabled
            console.log('App loaded - checking for JavaScript errors');
        </script>
    </body>
</html>

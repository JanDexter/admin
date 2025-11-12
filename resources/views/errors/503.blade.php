<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>503 - Service Unavailable</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#eef3ff] to-[#d4e3ff] px-4">
    <div class="max-w-2xl w-full text-center">
        <!-- Logo -->
        <div class="flex justify-center mb-8">
            <img src="{{ asset('img/logo.png') }}" alt="CO-Z Logo" class="h-20 w-auto" />
        </div>

        <!-- Error Message -->
        <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
            <h1 class="text-6xl md:text-8xl font-bold text-[#2f4686] mb-4">503</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-4">We're Taking a Quick Break!</h2>
            <p class="text-gray-600 text-lg mb-4 max-w-lg mx-auto">
                🔧 We're currently doing some maintenance to make things even better for you. Grab a coffee and check back in a few minutes!
            </p>
            <p class="text-gray-500 text-base mb-8 max-w-md mx-auto italic">
                Our co-workspace is temporarily closed for improvements
            </p>

            <!-- Refresh Button -->
            <button
                onclick="window.location.reload()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                </svg>
                Try Again
            </button>
        </div>

        <!-- Footer -->
        <p class="mt-8 text-sm text-gray-600">
            © {{ date('Y') }} CO-Z Co-Workspace & Study Hub. All rights reserved.
        </p>
    </div>
</body>
</html>

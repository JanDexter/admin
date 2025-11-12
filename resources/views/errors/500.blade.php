<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
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
            <h1 class="text-6xl md:text-8xl font-bold text-[#2f4686] mb-4">500</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-4">Oops! Our Servers Are Having a Moment</h2>
            <p class="text-gray-600 text-lg mb-4 max-w-lg mx-auto">
                😅 Something unexpected happened on our end. Don't worry, it's not you - it's us. Our team has been notified and we're on it!
            </p>
            <p class="text-gray-500 text-base mb-8 max-w-md mx-auto italic">
                Even the best co-working spaces have technical difficulties
            </p>

            <!-- Action Button -->
            <a
                href="{{ route('customer.view') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#2f4686] hover:bg-[#3956a3] text-white font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                </svg>
                Back to Home
            </a>
        </div>

        <!-- Footer -->
        <p class="mt-8 text-sm text-gray-600">
            © {{ date('Y') }} CO-Z Co-Workspace & Study Hub. All rights reserved.
        </p>
    </div>
</body>
</html>

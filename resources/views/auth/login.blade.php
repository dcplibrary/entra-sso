<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-indigo-500 via-purple-500 to-purple-700 min-h-screen flex items-center justify-center p-5">
    <div class="bg-white rounded-2xl shadow-2xl p-12 max-w-md w-full">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ config('app.name') }}</h1>
            <p class="text-gray-600 text-sm">Sign in to continue</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-5 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <a href="{{ route('entra.login') }}" class="flex items-center justify-center w-full px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-base transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-blue-600/30">
            <svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-3">
                <rect width="10" height="10" fill="currentColor"/>
                <rect x="11" width="10" height="10" fill="currentColor" opacity="0.8"/>
                <rect y="11" width="10" height="10" fill="currentColor" opacity="0.8"/>
                <rect x="11" y="11" width="10" height="10" fill="currentColor" opacity="0.6"/>
            </svg>
            Sign in with Microsoft
        </a>

        <div class="text-center mt-6 text-gray-600 text-xs">
            Secured by Azure Active Directory
        </div>
    </div>
</body>
</html>

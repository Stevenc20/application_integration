<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAVBAR</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">

<!-- NAVBAR -->
<nav id="mainNavbar"
    class="sticky top-0 z-40 w-full h-16 px-4 md:px-6 flex items-center justify-between
           bg-gray-900 border-b border-gray-800 transition-all duration-300">

    <!-- LEFT -->
    <div class="flex items-center gap-4">

        <button id="sidebarToggle"
            class="md:hidden text-gray-300 text-xl hover:text-white transition">
            ☰
        </button>

        <!-- Search -->
        <div class="hidden sm:flex items-center gap-2 bg-gray-800 px-3 py-1.5 rounded-full
                    focus-within:ring-2 focus-within:ring-red-500/40 transition">

            <svg class="w-4 h-4 text-gray-400"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.3-4.3M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16z"/>
            </svg>

            <input type="text"
                placeholder="Search..."
                class="bg-transparent text-sm text-gray-200 placeholder-gray-400 focus:outline-none w-32 md:w-48">
        </div>

    </div>

    <!-- RIGHT -->
    <div class="relative">

        <button id="userMenuBtn"
            class="flex items-center gap-3 px-2 py-1 rounded-xl hover:bg-gray-800/70 transition">

            <img
                src="{{ asset('images/while_pp.jpg') }}"
                class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-700 hover:ring-red-500 transition"
            />

            <div class="hidden sm:flex flex-col text-left leading-tight">
                <span class="text-sm font-semibold text-gray-200">
                    {{ auth()->user()->name ?? 'Guest' }}
                </span>
                <span class="text-xs text-gray-400 capitalize">
                    {{ auth()->user()->role ?? '-' }}
                </span>
            </div>

        </button>

        <!-- DROPDOWN -->
        <div id="userDropdown"
            class="absolute right-0 mt-3 w-60 bg-gray-900/95 backdrop-blur-md border border-gray-800 
                   rounded-2xl shadow-xl opacity-0 scale-95 invisible 
                   transition-all duration-200 ease-out z-50">

            <!-- USER INFO -->
            <div class="px-5 py-4 border-b border-gray-800">
                <p class="text-base font-semibold text-white">
                    {{ auth()->user()->name ?? 'Guest' }}
                </p>
                <p class="text-sm text-gray-400">
                    {{ auth()->user()->role ?? '-' }}
                </p>
            </div>

            <!-- MENU -->
            <div class="py-2">

                <a href="#"
                    class="flex items-center gap-3 px-5 py-3 text-base text-gray-300 
                           hover:bg-gray-800 hover:text-white transition rounded-xl mx-2">
                    👤 Profile
                </a>

                <a href="{{ route('logout') }}"
                    class="flex items-center gap-3 px-5 py-3 text-base text-red-400 
                           hover:bg-red-500/10 hover:text-red-300 transition rounded-xl mx-2">
                    🚪 Logout
                </a>

            </div>

        </div>

    </div>

</nav>
</body>
</html>
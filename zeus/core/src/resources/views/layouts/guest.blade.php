<!DOCTYPE html>
<html lang="vi" x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICan Dashboard')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        // Dark theme colors (matching theme.png - flat, neutral)
                        'zeus-dark': '#0B0D0F',
                        'zeus-darker': '#080A0C',
                        'zeus-card': '#12151A',
                        'zeus-card-light': '#1A1D24',
                        'zeus-border': '#2A2E35',
                        'zeus-accent': '#3B82F6',
                        'zeus-accent-light': '#60A5FA',
                        'zeus-text': '#E5E7EB',
                        'zeus-text-muted': '#9CA3AF',
                        // Light theme colors
                        'light-bg': '#F8FAFC',
                        'light-card': '#FFFFFF',
                        'light-card-alt': '#F1F5F9',
                        'light-border': '#E2E8F0',
                        'light-text': '#1E293B',
                        'light-text-muted': '#64748B',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Theme initialization (before Alpine) -->
    <script>
        if (localStorage.getItem('darkMode') === null) {
            localStorage.setItem('darkMode', 'true');
        }
        if (localStorage.getItem('darkMode') !== 'false') {
            document.documentElement.classList.add('dark');
        }
    </script>
    
    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>
<body class="min-h-screen bg-light-bg dark:bg-zeus-darker transition-colors duration-300">
    <!-- Theme Toggle Button (fixed position) -->
    <div class="fixed top-4 right-4 z-50">
        <button 
            @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
            class="p-2 rounded-lg bg-light-card dark:bg-zeus-card border border-light-border dark:border-zeus-border hover:bg-light-border dark:hover:bg-zeus-border transition shadow-sm"
            :title="darkMode ? 'Chuyển sang Light mode' : 'Chuyển sang Dark mode'"
        >
            <!-- Sun icon (shown in dark mode) -->
            <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <!-- Moon icon (shown in light mode) -->
            <svg x-show="!darkMode" x-cloak class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </div>
    
    @yield('content')
    
    @stack('scripts')
</body>
</html>

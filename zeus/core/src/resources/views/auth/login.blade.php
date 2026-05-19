@extends('layouts.guest')

@section('title', 'Đăng nhập - ICan Dashboard')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-light-bg dark:bg-zeus-darker py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-zeus-accent rounded-2xl shadow-lg mb-4">
                <span class="text-4xl">⚡</span>
            </div>
            <h1 class="text-4xl font-bold text-light-text dark:text-zeus-text">ICan Dashboard</h1>
            <h2 class="mt-4 text-xl text-light-text-muted dark:text-zeus-text-muted">Đăng nhập quản trị</h2>
        </div>
        
        <div class="mt-8 bg-light-card dark:bg-zeus-card rounded-xl shadow-lg dark:shadow-2xl p-8 border border-light-border dark:border-zeus-border">
            @if ($errors->any())
            <div class="mb-4 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6" id="login-form">
                @csrf
                
                {{-- Hidden field for CSRF token refresh --}}
                <input type="hidden" name="_token_timestamp" value="{{ time() }}">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-light-text dark:text-zeus-text">
                        Tên đăng nhập
                    </label>
                    <div class="mt-1">
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            autocomplete="username" 
                            required 
                            value="{{ old('username') }}"
                            class="appearance-none block w-full px-4 py-3 bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:outline-none focus:ring-2 focus:ring-zeus-accent focus:border-zeus-accent transition"
                            placeholder="admin"
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-light-text dark:text-zeus-text">
                        Mật khẩu
                    </label>
                    <div class="mt-1">
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            autocomplete="current-password" 
                            required
                            class="appearance-none block w-full px-4 py-3 bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border rounded-lg text-light-text dark:text-zeus-text placeholder-light-text-muted dark:placeholder-zeus-text-muted focus:outline-none focus:ring-2 focus:ring-zeus-accent focus:border-zeus-accent transition"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            {{ old('remember') ? 'checked' : '' }}
                            class="h-4 w-4 bg-light-card-alt dark:bg-zeus-card-light border-light-border dark:border-zeus-border text-zeus-accent focus:ring-zeus-accent focus:ring-offset-light-card dark:focus:ring-offset-zeus-card rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-light-text-muted dark:text-zeus-text-muted">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>
                </div>

                <div>
                    <button 
                        type="submit"
                        id="login-button"
                        class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-zeus-accent hover:bg-zeus-accent-light focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-light-card dark:focus:ring-offset-zeus-card focus:ring-zeus-accent transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="login-button-text">Đăng nhập</span>
                        <span id="login-button-loading" class="hidden items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Đang tổng hợp dữ liệu...</span>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-4 text-center text-sm text-light-text-muted dark:text-zeus-text-muted">
            ICan Learning Management System
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Function to show loading state on button
    function showLoginLoading() {
        const button = document.getElementById('login-button');
        const buttonText = document.getElementById('login-button-text');
        const buttonLoading = document.getElementById('login-button-loading');
        
        button.disabled = true;
        buttonText.classList.add('hidden');
        buttonLoading.classList.remove('hidden');
        buttonLoading.classList.add('flex');
    }
    
    // Function to hide loading state on button
    function hideLoginLoading() {
        const button = document.getElementById('login-button');
        const buttonText = document.getElementById('login-button-text');
        const buttonLoading = document.getElementById('login-button-loading');
        
        button.disabled = false;
        buttonText.classList.remove('hidden');
        buttonLoading.classList.add('hidden');
        buttonLoading.classList.remove('flex');
    }

    // Refresh CSRF token before form submission to prevent 419 errors
    document.getElementById('login-form').addEventListener('submit', function(e) {
        const button = document.getElementById('login-button');
        const tokenInput = document.querySelector('input[name="_token"]');
        const timestampInput = document.querySelector('input[name="_token_timestamp"]');
        const currentTime = Math.floor(Date.now() / 1000);
        const tokenTime = parseInt(timestampInput.value) || 0;
        
        // Show loading state immediately on submit
        showLoginLoading();
        
        // If token is older than 30 minutes, refresh it
        if (currentTime - tokenTime > 1800) {
            e.preventDefault();
            
            // Fetch fresh CSRF token
            fetch('{{ route("login") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'text/html',
                }
            })
            .then(response => response.text())
            .then(html => {
                // Extract new CSRF token from response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newToken = doc.querySelector('input[name="_token"]');
                const newMeta = doc.querySelector('meta[name="csrf-token"]');
                
                if (newToken) {
                    tokenInput.value = newToken.value;
                    timestampInput.value = currentTime;
                    
                    // Also update meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag && newMeta) {
                        metaTag.content = newMeta.content;
                    }
                    
                    // Re-submit the form
                    document.getElementById('login-form').submit();
                } else {
                    // Fallback: reload the page
                    window.location.reload();
                }
            })
            .catch(() => {
                // On error, reload the page
                hideLoginLoading();
                window.location.reload();
            });
        }
        // If token is fresh, the form will submit normally with loading indicator shown
    });
    
    // Auto-refresh token every 25 minutes if page stays open
    setInterval(function() {
        fetch('{{ route("login") }}', {
            method: 'GET',
            headers: {
                'Accept': 'text/html',
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newToken = doc.querySelector('input[name="_token"]');
            
            if (newToken) {
                const tokenInput = document.querySelector('input[name="_token"]');
                const timestampInput = document.querySelector('input[name="_token_timestamp"]');
                if (tokenInput) {
                    tokenInput.value = newToken.value;
                }
                if (timestampInput) {
                    timestampInput.value = Math.floor(Date.now() / 1000);
                }
                
                // Update meta tag
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                const newMeta = doc.querySelector('meta[name="csrf-token"]');
                if (metaTag && newMeta) {
                    metaTag.content = newMeta.content;
                }
            }
        })
        .catch(() => {
            // Silent fail for background refresh
        });
    }, 25 * 60 * 1000); // 25 minutes
</script>
@endpush

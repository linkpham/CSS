@props(['programName' => 'OMO'])

<div class="bg-light-card dark:bg-zeus-card rounded-xl p-8 md:p-12 border border-light-border dark:border-zeus-border shadow-sm">
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-500/10 dark:bg-blue-500/20 mb-6">
            <svg class="w-10 h-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        
        <h3 class="text-xl md:text-2xl font-bold text-light-text dark:text-zeus-text mb-3">
            🏢 Gói {{ $programName }}
        </h3>
        
        <p class="text-light-text-muted dark:text-zeus-text-muted text-base md:text-lg mb-6 max-w-lg mx-auto">
            Các chỉ số cho gói {{ $programName }} đang được phát triển và sẽ được cập nhật trong thời gian tới.
        </p>
        
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500/10 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-lg text-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Sẽ cập nhật các chỉ số sau</span>
        </div>
        
        <div class="mt-8 p-4 bg-light-card-alt dark:bg-zeus-card-light rounded-lg border border-light-border dark:border-zeus-border inline-block">
            <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">
                <span class="font-medium">Nguồn dữ liệu:</span> 
                <code class="px-1 py-0.5 bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded text-xs">tbl_teach_languages</code> 
                với ID = <code class="px-1 py-0.5 bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded text-xs">550</code>
            </p>
        </div>
    </div>
</div>

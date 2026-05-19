@props(['activeProgram' => 'all', 'baseRoute' => 'dashboard'])

{{-- 
    Phase 137: KPI Program filter tabs - ALL, SPEAKWELL, EASY SPEAK
    Phase 138: Made reusable with baseRoute prop for Daily Ops page
    Phase 141: Client-side tab switching via embedded pre-rendered HTML (no PJAX/fetch)
    Phase 142: Removed SPEAKWELL and EASY SPEAK tabs (caching too slow), kept only All indicator
--}}
<div class="mb-3 md:mb-4">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium
        bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 border border-blue-500/20 text-blue-600 dark:text-blue-400">
        <span>📊</span>
        <span>All</span>
        <span class="info-tooltip hidden md:inline-flex">ⓘ
            <span class="tooltip-content tooltip-wide">
                <span class="tooltip-label">tất cả khóa học</span><br>
                Bao gồm cả SPEAKWELL và EASY SPEAK.<br><br>
                <span class="tooltip-label">nguồn dữ liệu</span><br>
                Bảng: <span class="tooltip-table">tbl_teach_languages</span><br>
                <span class="tooltip-sql">ordles_tlang_id IN (533, 558, 560, 562, 580, 581, 564, 567, 568, 569, 416, 415, 414, 413, 571, 572, 574, 575, 576, 389, 390, 392, 405, 406, 407, 411, 412, 577, 586, 585, 584, 582, 404, 403, 583, 471)</span>
            </span>
        </span>
    </div>
</div>

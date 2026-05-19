@props(['type' => 'card', 'count' => 1])

@for($i = 0; $i < $count; $i++)
@switch($type)
    @case('card')
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm animate-pulse">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-light-card-alt dark:bg-zeus-card-light rounded-lg"></div>
                <div class="flex-1">
                    <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/3 mb-2"></div>
                    <div class="h-3 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/2"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div class="h-8 bg-light-card-alt dark:bg-zeus-card-light rounded w-full"></div>
                <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-2/3"></div>
            </div>
        </div>
        @break

    @case('stat')
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border shadow-sm animate-pulse">
            <div class="text-center">
                <div class="h-8 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/2 mx-auto mb-2"></div>
                <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-3/4 mx-auto"></div>
            </div>
        </div>
        @break

    @case('chart')
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 md:p-6 border border-light-border dark:border-zeus-border shadow-sm animate-pulse">
            <div class="h-5 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/4 mb-4"></div>
            <div class="h-48 md:h-64 bg-light-card-alt dark:bg-zeus-card-light rounded"></div>
        </div>
        @break

    @case('table')
        <div class="bg-light-card dark:bg-zeus-card rounded-xl p-4 border border-light-border dark:border-zeus-border shadow-sm animate-pulse">
            <div class="h-5 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/4 mb-4"></div>
            <div class="space-y-3">
                @for($j = 0; $j < 5; $j++)
                <div class="flex gap-4">
                    <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/4"></div>
                    <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/3"></div>
                    <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/4"></div>
                </div>
                @endfor
            </div>
        </div>
        @break

    @case('stats-row')
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2 md:gap-4 animate-pulse">
            @for($j = 0; $j < 7; $j++)
            <div class="text-center p-2 md:p-4 bg-light-card dark:bg-zeus-card rounded-lg border border-light-border dark:border-zeus-border">
                <div class="h-6 bg-light-card-alt dark:bg-zeus-card-light rounded w-1/2 mx-auto mb-2"></div>
                <div class="h-3 bg-light-card-alt dark:bg-zeus-card-light rounded w-3/4 mx-auto"></div>
            </div>
            @endfor
        </div>
        @break

    @default
        <div class="animate-pulse">
            <div class="h-4 bg-light-card-alt dark:bg-zeus-card-light rounded w-full"></div>
        </div>
@endswitch
@endfor

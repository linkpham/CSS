@extends('layouts.app')

@section('title', 'ICan Dashboard - Vận hành Hôm nay')
@section('page-title', 'Vận hành Hôm nay')

@section('content')
@php
    $activeProgram = $activeProgram ?? request('program', 'all');
@endphp

<div class="space-y-4 md:space-y-6">
    <!-- Phase 142: Only All indicator (removed SPEAKWELL/EASY SPEAK tabs for caching performance) -->
    <x-kpi-program-tabs :activeProgram="$activeProgram" baseRoute="dashboard.daily-ops" />
    
    <div id="program-content" class="space-y-4 md:space-y-6">
    @include('dashboard.partials.daily-ops-program-content')
    </div>
</div>
@endsection

@push('scripts')
<script data-page-script>
@include('dashboard.partials.daily-ops-page-script')
</script>
@endpush

@extends('layouts.app')

@section('title', 'ICan Dashboard - Tổng quan')
@section('page-title', 'Tổng quan Hệ thống')

@section('content')

<div class="space-y-4 md:space-y-6">
    <!-- Phase 142: Only All indicator (removed SPEAKWELL/EASY SPEAK tabs for caching performance) -->
    <x-kpi-program-tabs :activeProgram="$activeProgram" />
    
    <div id="program-content" class="space-y-4 md:space-y-6" x-data="dashboardFilter()">
    @include('dashboard.partials.index-program-content')
    </div>
</div>
@endsection

@push('scripts')
<script data-page-script>
@include('dashboard.partials.index-page-script')
</script>
@endpush

<!DOCTYPE html>
<html lang="vi" x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICan Dashboard')</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Alpine.js with Collapse Plugin -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Theme initialization (before Alpine) -->
    <script>
        // Set initial dark mode based on localStorage or system preference
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
        
        /* Info tooltip for data source indicator */
        .info-tooltip {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            margin-left: 4px;
            border-radius: 50%;
            background-color: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
            cursor: help;
            font-size: 10px;
            font-weight: 600;
            vertical-align: middle;
            transition: all 0.2s ease;
        }
        .info-tooltip:hover {
            background-color: rgba(59, 130, 246, 0.2);
        }
        .info-tooltip .tooltip-content {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            min-width: 220px;
            max-width: 300px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 400;
            line-height: 1.5;
            text-align: left;
            z-index: 9999;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            pointer-events: auto;
        }
        .info-tooltip:hover .tooltip-content,
        .info-tooltip .tooltip-content:hover {
            visibility: visible;
            opacity: 1;
        }
        /* Allow clicking on tooltip content - keep visible when hovering content */
        .info-tooltip .tooltip-content {
            /* Add a bridge between icon and tooltip so hover doesn't break */
            padding-bottom: 16px;
            margin-bottom: -16px;
        }
        .info-tooltip .tooltip-content::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 6px;
            border-style: solid;
        }
        /* Dark mode tooltip */
        .dark .info-tooltip {
            background-color: rgba(96, 165, 250, 0.15);
            color: #60A5FA;
        }
        .dark .info-tooltip:hover {
            background-color: rgba(96, 165, 250, 0.25);
        }
        .dark .info-tooltip .tooltip-content {
            background-color: #1E293B;
            color: #E2E8F0;
            border: 1px solid #334155;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        .dark .info-tooltip .tooltip-content::after {
            border-color: #1E293B transparent transparent transparent;
        }
        /* Light mode tooltip */
        .info-tooltip .tooltip-content {
            background-color: #FFFFFF;
            color: #334155;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .info-tooltip .tooltip-content::after {
            border-color: #FFFFFF transparent transparent transparent;
        }
        .tooltip-label {
            color: #64748B;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .dark .tooltip-label {
            color: #94A3B8;
        }
        .tooltip-table {
            font-family: 'Monaco', 'Consolas', monospace;
            background-color: rgba(59, 130, 246, 0.1);
            color: #3B82F6;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
        }
        .dark .tooltip-table {
            background-color: rgba(96, 165, 250, 0.15);
            color: #60A5FA;
        }
        /* Wide tooltip for SQL explanations */
        .tooltip-wide {
            min-width: 320px !important;
            max-width: 450px !important;
        }
        /* Fix tooltip clipping inside <th> elements by using fixed positioning */
        th .info-tooltip .tooltip-content {
            position: fixed !important;
            bottom: auto !important;
            left: 0 !important;
            transform: none !important;
            /* Initially off-screen until JS positions it */
            top: -9999px !important;
        }
        /* Arrow when tooltip is shown below the icon */
        th .info-tooltip .tooltip-content[data-tooltip-below]::after {
            top: auto !important;
            bottom: 100% !important;
            border-color: transparent transparent #FFFFFF transparent !important;
        }
        .dark th .info-tooltip .tooltip-content[data-tooltip-below]::after {
            border-color: transparent transparent #1E293B transparent !important;
        }
        /* Adjust padding bridge for th tooltips */
        th .info-tooltip .tooltip-content {
            padding-bottom: 10px !important;
            margin-bottom: 0 !important;
        }
        th .info-tooltip .tooltip-content[data-tooltip-below] {
            padding-top: 16px !important;
            margin-top: -16px !important;
            padding-bottom: 10px !important;
        }
        /* SQL code display in tooltips */
        .tooltip-sql {
            display: block;
            margin-top: 6px;
            padding: 8px;
            font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
            font-size: 9px;
            line-height: 1.4;
            background-color: rgba(15, 23, 42, 0.05);
            border-radius: 4px;
            border-left: 2px solid #3B82F6;
            color: #475569;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .dark .tooltip-sql {
            background-color: rgba(0, 0, 0, 0.3);
            color: #94A3B8;
        }
        
        /* Hide SQL tooltips and related content for non-privileged users */
        .hide-sql .tooltip-sql,
        .hide-sql .tooltip-content code,
        .hide-sql .info-tooltip .tooltip-content .tooltip-sql {
            display: none !important;
        }
        /* Simplify tooltip for non-privileged users - hide the SQL label */
        .hide-sql .tooltip-content span.tooltip-label:has(+ br + code),
        .hide-sql .tooltip-content span.tooltip-label:has(+ br + span.tooltip-sql) {
            display: none !important;
        }
        
        /* Sidebar styles */
        .sidebar-active {
            background-color: rgba(59, 130, 246, 0.15);
            color: #3B82F6;
            border-radius: 0.5rem;
            margin: 0 0.5rem;
            border-left: 3px solid #3B82F6;
        }
        .dark .sidebar-active {
            background-color: rgba(59, 130, 246, 0.15);
            color: #60A5FA;
            border-left-color: #60A5FA;
        }
        .sidebar-link {
            transition: all 0.2s ease;
        }
        .sidebar-link:hover:not(.sidebar-active) {
            background-color: rgba(59, 130, 246, 0.08);
            border-radius: 0.5rem;
            margin: 0 0.5rem;
        }
        
        /* Custom scrollbar - Dark theme */
        .dark ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .dark ::-webkit-scrollbar-track {
            background: #12151A;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #3A3F47;
            border-radius: 4px;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #4B5563;
        }
        
        /* Custom scrollbar - Light theme */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        
        /* Smooth theme transition */
        html {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* ============================================
           Mobile Responsive Fixes - No Horizontal Scroll
           ============================================ */
        
        /* Prevent horizontal overflow on mobile */
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        /* Ensure the main content container doesn't overflow */
        .flex.h-screen {
            max-width: 100vw;
            overflow-x: hidden;
        }
        
        /* Main content area - prevent horizontal overflow */
        main {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Content wrapper - prevent child elements from causing overflow */
        .p-3, .p-6 {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* KPI Cards - Ensure no line break on mobile */
        .kpi-value {
            font-size: clamp(1rem, 5vw, 2rem);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .kpi-label {
            font-size: clamp(0.65rem, 2.5vw, 0.875rem);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Responsive KPI grid */
        .kpi-grid {
            display: grid;
            gap: 0.5rem;
            grid-template-columns: repeat(2, 1fr);
        }
        
        @media (min-width: 640px) {
            .kpi-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 0.75rem;
            }
        }
        
        @media (min-width: 1024px) {
            .kpi-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 1rem;
            }
        }
        
        /* KPI card compact styling for mobile */
        .kpi-card {
            padding: 0.75rem;
            min-width: 0; /* Allow flex items to shrink */
        }
        
        @media (min-width: 768px) {
            .kpi-card {
                padding: 1rem;
            }
        }
        
        /* Chart responsive legend */
        .chart-container {
            position: relative;
            width: 100%;
            min-height: 200px;
        }
        
        @media (max-width: 640px) {
            .chart-container canvas {
                max-height: 250px;
            }
        }
        
        /* Ensure tables scroll horizontally inside container */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -0.75rem;
            padding: 0 0.75rem;
        }
        
        @media (min-width: 768px) {
            .table-container {
                margin: 0;
                padding: 0;
            }
        }
        
        /* Section loading state with skeleton */
        .section-loading {
            position: relative;
            min-height: 100px;
        }
        
        .section-loading::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(2px);
            border-radius: inherit;
            z-index: 10;
        }
        
        .dark .section-loading::before {
            background: rgba(11, 13, 15, 0.8);
        }
        
        .section-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 32px;
            height: 32px;
            border: 3px solid rgba(59, 130, 246, 0.2);
            border-top-color: #3B82F6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            z-index: 11;
        }
        
        /* Mobile-friendly tabs */
        .tabs-container {
            display: flex;
            gap: 0.25rem;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 0.25rem;
        }
        
        .tabs-container::-webkit-scrollbar {
            display: none;
        }
        
        .tab-button {
            flex-shrink: 0;
            white-space: nowrap;
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        @media (min-width: 640px) {
            .tab-button {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
        }
        
        /* ============================================
           Page Loading Overlay & Spinner Styles
           ============================================ */
        
        /* Main page loading overlay */
        .page-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        
        .dark .page-loading-overlay {
            background: linear-gradient(135deg, #0B0D0F 0%, #12151A 100%);
        }
        
        .page-loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        
        /* Spinner container */
        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        /* Main spinner */
        .spinner {
            width: 56px;
            height: 56px;
            position: relative;
        }
        
        .spinner::before,
        .spinner::after {
            content: '';
            position: absolute;
            border-radius: 50%;
        }
        
        .spinner::before {
            width: 100%;
            height: 100%;
            border: 4px solid #E2E8F0;
            border-top-color: #3B82F6;
            animation: spin 1s linear infinite;
        }
        
        .dark .spinner::before {
            border-color: #2A2E35;
            border-top-color: #60A5FA;
        }
        
        .spinner::after {
            width: 32px;
            height: 32px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 3px solid transparent;
            border-top-color: #60A5FA;
            border-bottom-color: #60A5FA;
            animation: spin 0.6s linear infinite reverse;
        }
        
        .dark .spinner::after {
            border-top-color: #3B82F6;
            border-bottom-color: #3B82F6;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Loading text */
        .loading-text {
            color: #64748B;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            animation: pulse-text 1.5s ease-in-out infinite;
        }
        
        .dark .loading-text {
            color: #9CA3AF;
        }
        
        @keyframes pulse-text {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        /* Loading dots animation */
        .loading-dots::after {
            content: '';
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: ''; }
            40% { content: '.'; }
            60% { content: '..'; }
            80%, 100% { content: '...'; }
        }
        
        /* Section/Card loading skeleton */
        .skeleton-loading {
            position: relative;
            overflow: hidden;
        }
        
        .skeleton-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.4) 50%,
                transparent 100%
            );
            animation: shimmer 1.5s infinite;
        }
        
        .dark .skeleton-loading::after {
            background: linear-gradient(
                90deg,
                transparent 0%,
                rgba(255, 255, 255, 0.1) 50%,
                transparent 100%
            );
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Inline/Small spinner for buttons and inputs */
        .spinner-inline {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #E2E8F0;
            border-top-color: #3B82F6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
        }
        
        .dark .spinner-inline {
            border-color: #4B5563;
            border-top-color: #60A5FA;
        }
        
        .spinner-inline.spinner-sm {
            width: 12px;
            height: 12px;
            border-width: 1.5px;
        }
        
        .spinner-inline.spinner-lg {
            width: 24px;
            height: 24px;
            border-width: 3px;
        }
        
        /* Content loading overlay (for sections) */
        .content-loading {
            position: relative;
            min-height: 100px;
        }
        
        .content-loading::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(248, 250, 252, 0.9);
            backdrop-filter: blur(2px);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: inherit;
        }
        
        .dark .content-loading::before {
            background: rgba(11, 13, 15, 0.9);
        }
        
        .content-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 32px;
            height: 32px;
            border: 3px solid #E2E8F0;
            border-top-color: #3B82F6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            z-index: 11;
        }
        
        .dark .content-loading::after {
            border-color: #2A2E35;
            border-top-color: #60A5FA;
        }
        
        /* Logo pulse animation for loading screen */
        .logo-pulse {
            animation: logo-pulse 2s ease-in-out infinite;
        }
        
        @keyframes logo-pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 1;
            }
            50% { 
                transform: scale(1.1);
                opacity: 0.8;
            }
        }
        
        /* Progressive content reveal animation */
        .content-reveal {
            opacity: 0;
            transform: translateY(10px);
            animation: contentReveal 0.4s ease-out forwards;
        }
        
        @keyframes contentReveal {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Staggered animation delays for cards */
        .content-reveal:nth-child(1) { animation-delay: 0.05s; }
        .content-reveal:nth-child(2) { animation-delay: 0.1s; }
        .content-reveal:nth-child(3) { animation-delay: 0.15s; }
        .content-reveal:nth-child(4) { animation-delay: 0.2s; }
        .content-reveal:nth-child(5) { animation-delay: 0.25s; }
        .content-reveal:nth-child(6) { animation-delay: 0.3s; }
        .content-reveal:nth-child(7) { animation-delay: 0.35s; }
        .content-reveal:nth-child(8) { animation-delay: 0.4s; }
        
        /* Faster page transitions */
        .page-transition-enter {
            opacity: 0;
        }
        .page-transition-enter-active {
            opacity: 1;
            transition: opacity 0.2s ease-out;
        }
        
        /* Progress bar at top */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #3B82F6, #60A5FA);
            z-index: 10000;
            transition: width 0.3s ease;
        }
        
        /* ============================================
           Phase 164: Circular Segmented-Dot Progress
           Indicator for Data Filtering / Loading
           ============================================ */
        .filter-progress-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9998;
            transition: opacity 0.35s ease, visibility 0.35s ease;
        }
        .dark .filter-progress-overlay {
            background: rgba(11, 13, 15, 0.82);
        }
        .filter-progress-overlay.fp-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        .filter-progress-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .filter-progress-ring {
            position: relative;
            width: 120px;
            height: 120px;
        }
        .filter-progress-ring svg {
            width: 100%;
            height: 100%;
        }
        .filter-progress-pct {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 22px;
            font-weight: 700;
            color: #3B82F6;
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.5px;
        }
        .dark .filter-progress-pct {
            color: #60A5FA;
        }
        .filter-progress-dot {
            fill: rgba(59, 130, 246, 0.15);
            transition: fill 0.15s ease;
        }
        .dark .filter-progress-dot {
            fill: rgba(96, 165, 250, 0.12);
        }
        .filter-progress-dot.fp-active {
            fill: #3B82F6;
        }
        .filter-progress-dot.fp-current {
            fill: #3B82F6;
            animation: fp-dot-pulse 0.8s ease-in-out infinite;
        }
        .dark .filter-progress-dot.fp-active {
            fill: #60A5FA;
        }
        .dark .filter-progress-dot.fp-current {
            fill: #60A5FA;
        }
        @keyframes fp-dot-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .filter-progress-label {
            color: #64748B;
            font-size: 14px;
            font-weight: 500;
            animation: pulse-text 1.5s ease-in-out infinite;
        }
        .dark .filter-progress-label {
            color: #9CA3AF;
        }
    </style>
    
    @stack('styles')
</head>
<body class="min-h-screen bg-light-bg text-light-text dark:bg-zeus-dark dark:text-zeus-text transition-colors duration-300 overflow-x-hidden {{ session('can_view_sql') ? 'can-view-sql' : 'hide-sql' }}">
    <!-- Page Loading Overlay -->
    <div id="page-loading-overlay" class="page-loading-overlay">
        <div class="spinner-container">
            <!-- Logo with pulse animation -->
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center logo-pulse shadow-lg overflow-hidden">
                <img src="{{ asset('logo-ican.png') }}" alt="ICan Logo" class="h-12 w-auto object-contain">
            </div>
            <!-- Spinner -->
            <div class="spinner"></div>
            <!-- Loading text -->
            <p class="loading-text">Đang tải dữ liệu<span class="loading-dots"></span></p>
        </div>
    </div>

    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: window.innerWidth >= 768, mobileMenuOpen: false }" @resize.window="sidebarOpen = window.innerWidth >= 768">
        <!-- Mobile Sidebar Overlay -->
        <div 
            x-show="mobileMenuOpen" 
            x-cloak
            @click="mobileMenuOpen = false"
            class="fixed inset-0 bg-black/50 z-40 md:hidden transition-opacity"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        ></div>
        
        <!-- Sidebar -->
        <aside 
            class="bg-light-card dark:bg-zeus-darker min-h-screen flex-shrink-0 transition-all duration-300 border-r border-light-border dark:border-zeus-border fixed md:relative z-50 flex flex-col"
            :class="{ 
                'w-64': sidebarOpen, 
                'w-20': !sidebarOpen && window.innerWidth >= 768,
                'translate-x-0': mobileMenuOpen || window.innerWidth >= 768,
                '-translate-x-full': !mobileMenuOpen && window.innerWidth < 768,
                'md:translate-x-0': true
            }"
        >
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2" x-show="sidebarOpen || window.innerWidth < 768">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('logo-ican.png') }}" alt="ICan Logo" class="h-8 w-auto object-contain">
                        </div>
                    </div>
                    <!-- Desktop Toggle -->
                    <button @click="sidebarOpen = !sidebarOpen" class="hidden md:block text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text p-2 rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <!-- Mobile Close Button -->
                    <button @click="mobileMenuOpen = false" class="md:hidden text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text p-2 rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Navigation Menu - Scrollable -->
            <nav class="flex-1 overflow-y-auto mt-4 px-2" x-data="{ speakwellOpen: true }">
                <!-- SpeakWell Product Section -->
                <div class="mb-2">
                    <button @click="speakwellOpen = !speakwellOpen" 
                            class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-light-text dark:text-zeus-text bg-gradient-to-r from-blue-500/10 to-purple-500/10 dark:from-blue-500/20 dark:to-purple-500/20 rounded-lg border border-blue-500/20 dark:border-blue-500/30 hover:from-blue-500/15 hover:to-purple-500/15 transition-all">
                        <span class="flex items-center gap-2">
                            <span class="text-base">🗣️</span>
                            <span x-show="sidebarOpen || window.innerWidth < 768">ICAN</span>
                        </span>
                        <svg x-show="sidebarOpen || window.innerWidth < 768" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': speakwellOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div x-show="speakwellOpen" x-collapse class="mt-1 space-y-0.5 pl-2">
                        <a href="{{ route('dashboard') }}" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">KPI</span>
                        </a>
                        
                        <a href="{{ route('dashboard.daily-ops') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.daily-ops') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Vận hành</span>
                        </a>
                        
                        @if(session('can_view_revenue'))
                        <a href="{{ route('dashboard.revenue') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.revenue') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Doanh thu</span>
                        </a>
                        @endif
                        
                        <a href="{{ route('dashboard.quality') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.quality') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Chất lượng</span>
                        </a>
                        
                        <a href="{{ route('dashboard.teachers') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.teachers') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Người dùng</span>
                        </a>
                        
                        <a href="{{ route('dashboard.learning-path') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.learning-path') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Funnel</span>
                        </a>
                        
                        @if(session('can_view_teacher_mgmt'))
                        <a href="{{ route('dashboard.teacher-management') }}?program=all" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.teacher-management') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Quản trị GV</span>
                        </a>
                        @endif
                        
                        <a href="{{ route('dashboard.lcms') }}" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('dashboard.lcms') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">LCMS</span>
                        </a>
                        
                        <a href="{{ route('csi.index') }}" 
                           @click="mobileMenuOpen = false"
                           class="sidebar-link flex items-center px-3 py-2 text-sm text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text {{ request()->routeIs('csi.*') ? 'sidebar-active' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="ml-2" x-show="sidebarOpen || window.innerWidth < 768">Chăm sóc CSI</span>
                        </a>
                    </div>
                </div>
                
                <!-- OMO Product Section - No submenu, direct link to placeholder page -->
                <div class="mb-2">
                    <a href="{{ route('dashboard') }}?program=omo" 
                       @click="mobileMenuOpen = false"
                       class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-light-text dark:text-zeus-text bg-gradient-to-r from-emerald-500/10 to-teal-500/10 dark:from-emerald-500/20 dark:to-teal-500/20 rounded-lg border border-emerald-500/20 dark:border-emerald-500/30 hover:from-emerald-500/15 hover:to-teal-500/15 transition-all {{ request('program') == 'omo' ? 'ring-2 ring-emerald-500/50' : '' }}">
                        <span class="flex items-center gap-2">
                            <span class="text-base">🏢</span>
                            <span x-show="sidebarOpen || window.innerWidth < 768">OMO</span>
                        </span>
                    </a>
                </div>
                
                <!-- CareSoft CSKH Section -->
                <div class="mb-2">
                    <a href="{{ route('caresoft.index') }}" 
                       @click="mobileMenuOpen = false"
                       class="w-full flex items-center justify-between px-4 py-2.5 text-sm font-semibold text-light-text dark:text-zeus-text bg-gradient-to-r from-cyan-500/10 to-blue-500/10 dark:from-cyan-500/20 dark:to-blue-500/20 rounded-lg border border-cyan-500/20 dark:border-cyan-500/30 hover:from-cyan-500/15 hover:to-blue-500/15 transition-all {{ request()->routeIs('caresoft.*') ? 'ring-2 ring-cyan-500/50' : '' }}">
                        <span class="flex items-center gap-2">
                            <span class="text-base">🎧</span>
                            <span x-show="sidebarOpen || window.innerWidth < 768">Care Soft</span>
                        </span>
                    </a>
                </div>
                

            </nav>
            
            <!-- User Profile Section - Fixed at bottom -->
            @if(session('admin_authenticated'))
            @php
                $adminName = session('admin_name', 'Admin');
                $adminEmail = session('admin_email', '');
                $adminRole = session('admin_role', 'Viewer');
                $adminInitials = strtoupper(substr($adminName, 0, 1) . (str_contains($adminName, ' ') ? substr(explode(' ', $adminName)[1] ?? '', 0, 1) : ''));
            @endphp
            <div class="flex-shrink-0 border-t border-light-border dark:border-zeus-border p-3" x-data="{ showProfileMenu: false, showAccountModal: false }">
                <!-- Profile Button -->
                <button 
                    @click="showProfileMenu = !showProfileMenu" 
                    class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition"
                    :class="{ 'justify-center': !sidebarOpen && window.innerWidth >= 768 }"
                >
                    <!-- Avatar -->
                    <div class="w-9 h-9 bg-gradient-to-br from-zeus-accent to-zeus-accent-light rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0">
                        {{ $adminInitials ?: 'A' }}
                    </div>
                    <!-- User Info (hidden when sidebar collapsed) -->
                    <div class="flex-1 text-left min-w-0" x-show="sidebarOpen || window.innerWidth < 768">
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text truncate">{{ $adminName }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted truncate">{{ $adminRole }}</p>
                    </div>
                    <!-- Chevron -->
                    <svg x-show="sidebarOpen || window.innerWidth < 768" class="w-4 h-4 text-light-text-muted dark:text-zeus-text-muted transition-transform flex-shrink-0" :class="{ 'rotate-180': showProfileMenu }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
                
                <!-- Profile Menu Dropdown (opens upward) -->
                <div 
                    x-show="showProfileMenu" 
                    x-cloak
                    @click.away="showProfileMenu = false"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-2"
                    class="absolute bottom-full left-3 right-3 mb-2 bg-light-card dark:bg-zeus-card rounded-lg shadow-xl border border-light-border dark:border-zeus-border overflow-hidden"
                >
                    <!-- User Info Header -->
                    <div class="px-4 py-3 bg-light-card-alt dark:bg-zeus-card-light border-b border-light-border dark:border-zeus-border">
                        <p class="text-sm font-medium text-light-text dark:text-zeus-text">{{ $adminName }}</p>
                        <p class="text-xs text-light-text-muted dark:text-zeus-text-muted">{{ $adminEmail }}</p>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="py-1">
                        <!-- Hồ sơ (Account) -->
                        <button @click="showAccountModal = true; showProfileMenu = false" class="w-full text-left px-4 py-2.5 text-sm text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition flex items-center gap-3">
                            <svg class="w-4 h-4 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Hồ sơ
                        </button>
                        
                        <!-- Đổi mật khẩu -->
                        <a href="https://admin.icanwork.vn/profile/change-password" target="_blank" class="w-full text-left px-4 py-2.5 text-sm text-light-text dark:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition flex items-center gap-3">
                            <svg class="w-4 h-4 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Đổi mật khẩu
                            <svg class="w-3 h-3 ml-auto text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                        
                        <div class="border-t border-light-border dark:border-zeus-border my-1"></div>
                        
                        <!-- Đăng xuất -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-red-500 dark:text-red-400 hover:bg-red-500/10 transition flex items-center gap-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Account Modal -->
                <div x-show="showAccountModal" x-cloak
                     class="fixed inset-0 z-[100] flex items-center justify-center"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50" @click="showAccountModal = false"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative bg-light-card dark:bg-zeus-card rounded-xl shadow-2xl w-full max-w-md mx-4 border border-light-border dark:border-zeus-border"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="showAccountModal = false">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-light-border dark:border-zeus-border">
                            <h3 class="text-lg font-semibold text-light-text dark:text-zeus-text flex items-center gap-2">
                                <svg class="w-5 h-5 text-zeus-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                Thông tin Tài khoản
                            </h3>
                            <button @click="showAccountModal = false" class="p-1 rounded-lg text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="px-6 py-5 space-y-4">
                            <!-- Avatar -->
                            <div class="flex justify-center mb-6">
                                <div class="w-24 h-24 bg-gradient-to-br from-zeus-accent to-zeus-accent-light rounded-full flex items-center justify-center text-white text-3xl font-bold shadow-lg">
                                    {{ $adminInitials ?: 'A' }}
                                </div>
                            </div>
                            
                            <!-- User Name -->
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wide">Tên người dùng</label>
                                <p class="text-sm text-light-text dark:text-zeus-text font-medium bg-light-card-alt dark:bg-zeus-card-light px-3 py-2 rounded-lg">
                                    {{ $adminName }}
                                </p>
                            </div>
                            
                            <!-- Email -->
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wide">Email</label>
                                <p class="text-sm text-light-text dark:text-zeus-text font-medium bg-light-card-alt dark:bg-zeus-card-light px-3 py-2 rounded-lg">
                                    {{ $adminEmail ?: 'N/A' }}
                                </p>
                            </div>
                            
                            <!-- Timezone -->
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wide">Múi giờ</label>
                                <p class="text-sm text-light-text dark:text-zeus-text font-medium bg-light-card-alt dark:bg-zeus-card-light px-3 py-2 rounded-lg flex items-center gap-2">
                                    <svg class="w-4 h-4 text-light-text-muted dark:text-zeus-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ config('app.timezone', 'Asia/Ho_Chi_Minh') }} (UTC+7)
                                </p>
                            </div>
                            
                            <!-- Role -->
                            <div class="space-y-1">
                                <label class="text-xs font-medium text-light-text-muted dark:text-zeus-text-muted uppercase tracking-wide">Vai trò</label>
                                <p class="text-sm">
                                    <span class="inline-block px-3 py-1 text-xs font-medium bg-zeus-accent/20 text-zeus-accent rounded-full">
                                        {{ $adminRole }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-6 py-4 bg-light-card-alt dark:bg-zeus-card-light rounded-b-xl border-t border-light-border dark:border-zeus-border">
                            <div class="flex justify-end gap-3">
                                <a href="https://admin.icanwork.vn/profile/change-password" target="_blank" class="px-4 py-2 text-sm font-medium text-zeus-accent bg-zeus-accent/10 hover:bg-zeus-accent/20 rounded-lg transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                    Đổi mật khẩu
                                </a>
                                <button @click="showAccountModal = false" class="px-4 py-2 text-sm font-medium text-light-text dark:text-zeus-text bg-light-border dark:bg-zeus-border hover:bg-light-text-muted/20 dark:hover:bg-zeus-text-muted/20 rounded-lg transition">
                                    Đóng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden bg-light-bg dark:bg-zeus-dark md:ml-0 min-w-0">
            <!-- Header -->
            <header class="bg-light-card dark:bg-zeus-card border-b border-light-border dark:border-zeus-border sticky top-0 z-30">
                <div class="px-3 md:px-6 py-3 md:py-4 flex items-center justify-between gap-2">
                    <div class="flex items-center space-x-2 md:space-x-3 min-w-0 flex-shrink">
                        <!-- Mobile Hamburger Button -->
                        <button 
                            @click="mobileMenuOpen = true" 
                            class="md:hidden p-2 rounded-lg text-light-text-muted dark:text-zeus-text-muted hover:text-light-text dark:hover:text-zeus-text hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition flex-shrink-0"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h2 class="text-base md:text-xl font-semibold text-light-text dark:text-zeus-text truncate">@yield('page-title', 'Dashboard')</h2>
                    </div>
                    <div class="flex items-center space-x-2 md:space-x-4 flex-shrink-0">
                        <span class="hidden sm:inline text-xs md:text-sm text-light-text-muted dark:text-zeus-text-muted whitespace-nowrap">{{ now()->format('d/m/Y H:i') }}</span>
                        
                        <!-- Theme Toggle Button -->
                        <button 
                            @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                            class="p-2 rounded-lg bg-light-card-alt dark:bg-zeus-card-light border border-light-border dark:border-zeus-border hover:bg-light-border dark:hover:bg-zeus-border transition"
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
                        
                        <!-- Cache refresh info tooltip and button -->
                        <div class="flex items-center gap-1 md:gap-2" x-data="cacheTimestamp()" x-init="fetchTimestamp()">
                            <span class="text-[10px] md:text-xs text-light-text-muted dark:text-zeus-text-muted hidden sm:flex items-center gap-1" :title="'Dữ liệu cập nhật lúc: ' + (refreshedAt || 'Chưa có')">
                                <svg class="w-3 h-3 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="refreshedAtFormatted || 'Đang tải...'"></span>
                            </span>
                            <button onclick="refreshDashboard()" id="refresh-btn" class="bg-zeus-accent hover:bg-zeus-accent-light text-white px-2.5 md:px-4 py-1.5 md:py-2 rounded-lg transition text-xs md:text-sm font-medium flex items-center gap-1.5 shadow-sm hover:shadow-md" title="Nhấn để xóa cache và tải dữ liệu mới nhất">
                                <svg id="refresh-icon" class="w-3.5 h-3.5 md:w-4 md:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span id="refresh-spinner" class="spinner-inline spinner-sm hidden"></span>
                                <span class="hidden sm:inline" id="refresh-text">Làm mới</span>
                            </button>
                        </div>
                        
                        <!-- Avatar Button - Opens sidebar on mobile (no dropdown) -->
                        @if(session('admin_authenticated'))
                        <button 
                            @click="mobileMenuOpen = true" 
                            class="md:hidden p-1 rounded-lg hover:bg-light-card-alt dark:hover:bg-zeus-card-light transition"
                            title="Mở menu"
                        >
                            <div class="w-8 h-8 bg-gradient-to-br from-zeus-accent to-zeus-accent-light rounded-full flex items-center justify-center text-white text-sm font-medium">
                                {{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}
                            </div>
                        </button>
                        @endif
                    </div>
                </div>
            </header>
            
            <!-- Phase 164: Filter Progress Overlay -->
            <div id="filter-progress-overlay" class="filter-progress-overlay fp-hidden">
                <div class="filter-progress-inner">
                    <div class="filter-progress-ring">
                        <svg id="filter-progress-svg" viewBox="0 0 100 100"></svg>
                        <div id="filter-progress-pct" class="filter-progress-pct">0%</div>
                    </div>
                    <p class="filter-progress-label">Đang tải dữ liệu<span class="loading-dots"></span></p>
                </div>
            </div>

            <!-- Page Content -->
            <div class="p-3 md:p-6">
                @yield('content')
            </div>
        </main>
    </div>
    
    <!-- Page Loading Script - Hide overlay when page is ready -->
    <script>
        // ICan Dashboard Loading Controller with Progress Bar
        const ICanLoader = {
            overlay: null,
            progressBar: null,
            progress: 0,
            progressInterval: null,
            
            init() {
                this.overlay = document.getElementById('page-loading-overlay');
                this.createProgressBar();
                
                // Start progress animation immediately
                this.startProgress();
                
                // Hide loader when DOM is interactive (faster than 'load')
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    this.hide();
                } else {
                    // Listen for DOMContentLoaded for faster response
                    document.addEventListener('DOMContentLoaded', () => this.hide());
                    // Also listen for load as backup
                    window.addEventListener('load', () => this.hide());
                }
                
                // Faster fallback timeout
                setTimeout(() => this.hide(), 5000);
            },
            
            createProgressBar() {
                this.progressBar = document.createElement('div');
                this.progressBar.className = 'progress-bar';
                this.progressBar.style.width = '0%';
                document.body.appendChild(this.progressBar);
            },
            
            startProgress() {
                this.progress = 0;
                this.progressInterval = setInterval(() => {
                    // Slow down as we approach 90%
                    if (this.progress < 30) {
                        this.progress += 5;
                    } else if (this.progress < 60) {
                        this.progress += 3;
                    } else if (this.progress < 85) {
                        this.progress += 1;
                    } else if (this.progress < 90) {
                        this.progress += 0.5;
                    }
                    
                    if (this.progressBar) {
                        this.progressBar.style.width = this.progress + '%';
                    }
                }, 50);
            },
            
            completeProgress() {
                if (this.progressInterval) {
                    clearInterval(this.progressInterval);
                }
                if (this.progressBar) {
                    this.progressBar.style.width = '100%';
                    setTimeout(() => {
                        this.progressBar.style.opacity = '0';
                        setTimeout(() => {
                            if (this.progressBar && this.progressBar.parentNode) {
                                this.progressBar.parentNode.removeChild(this.progressBar);
                            }
                        }, 200);
                    }, 150);
                }
            },
            
            show() {
                if (this.overlay) {
                    this.overlay.classList.remove('hidden');
                }
                this.createProgressBar();
                this.startProgress();
            },
            
            hide() {
                this.completeProgress();
                if (this.overlay) {
                    // Faster hide - reduced from 300ms to 100ms
                    setTimeout(() => {
                        this.overlay.classList.add('hidden');
                        // Add content reveal animation to main content
                        const mainContent = document.querySelector('main .p-3, main .p-6');
                        if (mainContent) {
                            mainContent.classList.add('page-transition-enter-active');
                        }
                    }, 100);
                }
            },
            
            // Helper method for showing loading on specific elements
            showSection(elementId) {
                const el = document.getElementById(elementId);
                if (el) {
                    el.classList.add('content-loading');
                }
            },
            
            hideSection(elementId) {
                const el = document.getElementById(elementId);
                if (el) {
                    el.classList.remove('content-loading');
                }
            }
        };
        
        // Initialize loader
        ICanLoader.init();
        
        // Show loading on page navigation (optional - for internal links)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]:not([target="_blank"]):not([href^="#"]):not([href^="javascript"])');
            if (link && link.href && !link.href.includes('#') && link.hostname === window.location.hostname) {
                // Check if it's a navigation to a different page
                const currentPath = window.location.pathname;
                const linkPath = new URL(link.href).pathname;
                if (currentPath !== linkPath) {
                    ICanLoader.show();
                }
            }
        });
        
        // Expose globally for use in other scripts
        window.ICanLoader = ICanLoader;
        
        /**
         * Phase 164: Circular Segmented-Dot Progress Indicator
         * Shows a ring of dots around a circle with percentage in the center
         * Used when filtering / loading data so users see visible progress.
         */
        const FilterProgress = {
            overlay: null,
            svg: null,
            pctEl: null,
            dots: [],
            totalDots: 24,
            progress: 0,
            interval: null,
            initialized: false,

            init() {
                if (this.initialized) return;
                this.overlay = document.getElementById('filter-progress-overlay');
                this.svg = document.getElementById('filter-progress-svg');
                this.pctEl = document.getElementById('filter-progress-pct');
                if (!this.svg) return;

                const r = 40, cx = 50, cy = 50;
                this.dots = [];

                for (let i = 0; i < this.totalDots; i++) {
                    const angle = (-90 + i * (360 / this.totalDots)) * Math.PI / 180;
                    const x = cx + r * Math.cos(angle);
                    const y = cy + r * Math.sin(angle);

                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x.toFixed(2));
                    circle.setAttribute('cy', y.toFixed(2));
                    circle.setAttribute('r', '3.5');
                    circle.classList.add('filter-progress-dot');
                    this.svg.appendChild(circle);
                    this.dots.push(circle);
                }
                this.initialized = true;
            },

            show() {
                this.init();
                if (!this.overlay) return;
                this.progress = 0;
                this._updateVisual();
                this.overlay.classList.remove('fp-hidden');

                if (this.interval) clearInterval(this.interval);
                this.interval = setInterval(() => {
                    if (this.progress < 30) {
                        this.progress += 3;
                    } else if (this.progress < 60) {
                        this.progress += 2;
                    } else if (this.progress < 85) {
                        this.progress += 1;
                    } else if (this.progress < 95) {
                        this.progress += 0.3;
                    }
                    this._updateVisual();
                }, 120);
            },

            hide() {
                if (!this.overlay) return;
                if (this.interval) {
                    clearInterval(this.interval);
                    this.interval = null;
                }
                this.progress = 100;
                this._updateVisual();

                setTimeout(() => {
                    if (this.overlay) this.overlay.classList.add('fp-hidden');
                }, 350);
            },

            _updateVisual() {
                const pct = Math.min(Math.round(this.progress), 100);
                if (this.pctEl) this.pctEl.textContent = pct + '%';

                const activeDots = Math.round(this.totalDots * pct / 100);
                this.dots.forEach((dot, i) => {
                    dot.classList.remove('fp-active', 'fp-current');
                    if (i < activeDots) {
                        dot.classList.add('fp-active');
                        if (i === activeDots - 1 && pct < 100) {
                            dot.classList.add('fp-current');
                        }
                    }
                });
            }
        };

        window.FilterProgress = FilterProgress;
        
        // Global Chart.js configuration for better mobile readability
        if (typeof Chart !== 'undefined') {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#9CA3AF' : '#64748B';
            const gridColor = isDarkMode ? '#2A2E35' : '#E2E8F0';
            
            // Set global defaults
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = gridColor;
            Chart.defaults.font.size = window.innerWidth < 640 ? 10 : 12;
            
            // Responsive legend configuration
            Chart.defaults.plugins.legend.labels.boxWidth = window.innerWidth < 640 ? 12 : 40;
            Chart.defaults.plugins.legend.labels.padding = window.innerWidth < 640 ? 8 : 10;
            Chart.defaults.plugins.legend.labels.font = {
                size: window.innerWidth < 640 ? 10 : 12
            };
            
            // Mobile-friendly tooltip
            Chart.defaults.plugins.tooltip.titleFont = {
                size: window.innerWidth < 640 ? 11 : 13
            };
            Chart.defaults.plugins.tooltip.bodyFont = {
                size: window.innerWidth < 640 ? 10 : 12
            };
            
            // Responsive point sizes
            Chart.defaults.elements.point.radius = window.innerWidth < 640 ? 2 : 4;
            Chart.defaults.elements.point.hoverRadius = window.innerWidth < 640 ? 4 : 6;
        }
        
        /**
         * Alpine.js component for cache timestamp display
         * Fetches and displays when dashboard data was last refreshed
         */
        function cacheTimestamp() {
            return {
                refreshedAt: null,
                refreshedAtFormatted: null,
                
                async fetchTimestamp() {
                    try {
                        const response = await fetch('/api/cache-refreshed-at');
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            this.refreshedAt = result.data.refreshed_at;
                            this.refreshedAtFormatted = result.data.refreshed_at_formatted 
                                ? 'Cập nhật: ' + result.data.refreshed_at_formatted
                                : 'Chưa có dữ liệu';
                        } else {
                            this.refreshedAtFormatted = 'Chưa có dữ liệu';
                        }
                    } catch (error) {
                        console.warn('Failed to fetch cache timestamp:', error);
                        this.refreshedAtFormatted = 'Lỗi tải';
                    }
                }
            };
        }
        
        /**
         * Phase 138: Refresh Dashboard - Clear cache, pre-cache all 3 programs, then reload
         * Shows progress as each program is being cached
         * This function is called by the "Làm mới" button
         */
        async function refreshDashboard() {
            const btn = document.getElementById('refresh-btn');
            const icon = document.getElementById('refresh-icon');
            const spinner = document.getElementById('refresh-spinner');
            const text = document.getElementById('refresh-text');
            
            // Disable button and show loading state
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-wait');
            
            // Hide icon, show spinner
            if (icon) icon.classList.add('hidden');
            if (spinner) spinner.classList.remove('hidden');
            if (text) text.textContent = 'Đang xóa cache...';

            // Phase 141: No client-side cache to clear (embedded data is part of the page)
            
            try {
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                
                // Step 1: Clear server-side cache
                const clearResponse = await fetch('/api/clear-cache', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                const clearData = await clearResponse.json();
                
                if (!clearData.success) {
                    console.warn('Cache clear failed:', clearData.message);
                }
                
                // Phase 142: Only 'all' program (removed speakwell/easyspeak tabs)
                const currentPath = window.location.pathname;
                
                try {
                    if (text) text.textContent = 'Cache ALL...';
                    await fetch(`${currentPath}?program=all`, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'text/html' }
                    });
                } catch (warmError) {
                    console.warn('Failed to warm cache:', warmError);
                }
                
                // Step 2: Reload the page
                if (text) text.textContent = 'Hoàn tất! Đang tải...';
                setTimeout(() => {
                    location.reload();
                }, 200);
                
            } catch (error) {
                // If API call fails, just reload the page normally
                console.warn('Cache refresh error:', error);
                if (text) text.textContent = 'Lỗi, đang tải lại...';
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }
    </script>
    
    <!-- Fix tooltip positioning inside <th> elements (Phase 170) -->
    <script>
    (function() {
        function positionThTooltip(tooltip) {
            var content = tooltip.querySelector('.tooltip-content');
            if (!content) return;

            var iconRect = tooltip.getBoundingClientRect();
            var cw = content.offsetWidth;
            var ch = content.offsetHeight;

            // Position above the icon by default
            var top = iconRect.top - ch - 8;
            var left = iconRect.left + iconRect.width / 2 - cw / 2;

            // If not enough space above, show below the icon
            if (top < 8) {
                top = iconRect.bottom + 8;
                // Flip arrow: handled via data attribute
                content.setAttribute('data-tooltip-below', '');
            } else {
                content.removeAttribute('data-tooltip-below');
            }

            // Keep within horizontal viewport bounds
            if (left < 8) left = 8;
            if (left + cw > window.innerWidth - 8) left = window.innerWidth - cw - 8;

            content.style.setProperty('top', top + 'px', 'important');
            content.style.setProperty('left', left + 'px', 'important');
        }

        // Use event delegation with mouseover (which bubbles, unlike mouseenter)
        document.addEventListener('mouseover', function(e) {
            var infoTooltip = e.target.closest('th .info-tooltip');
            if (!infoTooltip) return;
            positionThTooltip(infoTooltip);
        });

        // Re-position on scroll in case table scrolls horizontally
        document.addEventListener('scroll', function() {
            var hovered = document.querySelector('th .info-tooltip:hover');
            if (hovered) positionThTooltip(hovered);
        }, true);
    })();
    </script>

    @stack('scripts')
</body>
</html>

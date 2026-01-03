<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Web Downloader')</title>

    <!-- Google Fonts - Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1976D2;
            --primary-dark: #1565C0;
            --primary-light: #BBDEFB;
            --accent: #FF5722;
            --text-dark: #212121;
            --text-medium: #757575;
            --text-light: #BDBDBD;
            --divider: #E0E0E0;
            --bg-white: #FFFFFF;
            --bg-grey: #FAFAFA;
            --bg-card: #FFFFFF;
            --success: #4CAF50;
            --warning: #FF9800;
            --error: #F44336;
            --shadow-1: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            --shadow-2: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
            --shadow-3: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
            --sidebar-width: 240px;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-grey);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.5;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        a:hover {
            text-decoration: none;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--primary);
            color: white;
            align-items: center;
            padding: 0 8px;
            z-index: 100;
            box-shadow: var(--shadow-1);
        }

        .mobile-header-btn {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 50%;
        }

        .mobile-header-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .mobile-header-btn svg {
            width: 24px;
            height: 24px;
        }

        .mobile-header-title {
            flex: 1;
            font-size: 18px;
            font-weight: 500;
            margin-left: 4px;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-white);
            box-shadow: var(--shadow-1);
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 16px;
            background: var(--primary);
            color: white;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-logo svg {
            width: 28px;
            height: 28px;
        }

        .sidebar-logo h1 {
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 8px 0;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            color: var(--text-medium);
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-item:hover {
            background: var(--bg-grey);
        }

        .nav-item.active {
            color: var(--primary);
            background: var(--primary-light);
            border-right: 3px solid var(--primary);
        }

        .nav-item svg {
            width: 22px;
            height: 22px;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--divider);
        }

        .storage-widget {
            background: var(--bg-grey);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .storage-label {
            font-size: 11px;
            font-weight: 500;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .storage-size {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .storage-count {
            font-size: 12px;
            color: var(--text-medium);
            margin-bottom: 8px;
        }

        .storage-bar {
            height: 4px;
            background: var(--divider);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .storage-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
        }

        .storage-info {
            font-size: 11px;
            color: var(--text-light);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 24px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 24px;
            font-weight: 400;
            color: var(--text-dark);
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-medium);
        }

        /* View Toggle */
        .view-toggle {
            display: flex;
            background: var(--bg-white);
            border-radius: 4px;
            box-shadow: var(--shadow-1);
            overflow: hidden;
        }

        .view-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: none;
            border: none;
            color: var(--text-medium);
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .view-toggle-btn:hover {
            background: var(--bg-grey);
        }

        .view-toggle-btn.active {
            background: var(--primary);
            color: white;
        }

        .view-toggle-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border-radius: 4px;
            box-shadow: var(--shadow-1);
            margin-bottom: 16px;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--divider);
        }

        .card-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .card-body {
            padding: 16px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-medium);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            background: var(--bg-white);
            border: 1px solid var(--divider);
            border-radius: 4px;
            font-size: 14px;
            color: var(--text-dark);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary-light);
        }

        .form-input::placeholder {
            color: var(--text-light);
        }

        .form-row {
            display: flex;
            gap: 12px;
        }

        .form-row .form-input {
            flex: 1;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: var(--shadow-2);
        }

        .btn-secondary {
            background: var(--bg-grey);
            color: var(--text-medium);
        }

        .btn-secondary:hover {
            background: var(--divider);
        }

        .btn-danger {
            background: var(--error);
            color: white;
        }

        .btn-danger:hover {
            background: #D32F2F;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
        }

        .table th {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-medium);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--bg-grey);
            border-bottom: 1px solid var(--divider);
        }

        .table td {
            font-size: 14px;
            border-bottom: 1px solid var(--divider);
        }

        .table tbody tr:hover {
            background: var(--bg-grey);
        }

        /* List View for Downloads */
        .list-view {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .list-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            background: var(--bg-white);
            border-radius: 4px;
            box-shadow: var(--shadow-1);
        }

        .list-item-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-grey);
            border-radius: 4px;
            color: var(--text-medium);
        }

        .list-item-icon svg {
            width: 24px;
            height: 24px;
        }

        .list-item-content {
            flex: 1;
            min-width: 0;
        }

        .list-item-title {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .list-item-meta {
            font-size: 12px;
            color: var(--text-medium);
        }

        .list-item-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--divider);
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        /* Status Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
        }

        .badge-gray {
            background: #EEEEEE;
            color: #757575;
        }

        .badge-blue {
            background: #E3F2FD;
            color: #1976D2;
        }

        .badge-green {
            background: #E8F5E9;
            color: #388E3C;
        }

        .badge-red {
            background: #FFEBEE;
            color: #D32F2F;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
        }

        /* File Grid */
        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 16px;
        }

        .file-card {
            background: var(--bg-card);
            border-radius: 4px;
            box-shadow: var(--shadow-1);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .file-card:hover {
            box-shadow: var(--shadow-2);
        }

        .file-card-preview {
            height: 80px;
            background: var(--bg-grey);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }

        .file-card-preview svg {
            width: 36px;
            height: 36px;
        }

        .file-card-body {
            padding: 12px;
        }

        .file-card-name {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 4px;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .file-card-meta {
            font-size: 12px;
            color: var(--text-medium);
        }

        .file-card-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--divider);
            flex-wrap: wrap;
        }

        /* File List View */
        .file-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .file-list-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: var(--bg-white);
            border-radius: 4px;
            box-shadow: var(--shadow-1);
        }

        .file-list-item-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-grey);
            border-radius: 4px;
            color: var(--text-medium);
            flex-shrink: 0;
        }

        .file-list-item-icon svg {
            width: 24px;
            height: 24px;
        }

        .file-list-item-content {
            flex: 1;
            min-width: 0;
        }

        .file-list-item-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-list-item-meta {
            font-size: 12px;
            color: var(--text-medium);
        }

        .file-list-item-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--divider);
            margin-bottom: 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .tab {
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-medium);
            border-bottom: 2px solid transparent;
            transition: color 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .tab:hover {
            color: var(--primary);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-count {
            background: var(--bg-grey);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 6px;
        }

        /* Modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
            padding: 16px;
        }

        .modal {
            background: var(--bg-card);
            border-radius: 4px;
            box-shadow: var(--shadow-3);
            width: 100%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--divider);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .modal-close {
            padding: 8px;
            color: var(--text-medium);
            cursor: pointer;
            border: none;
            background: none;
            border-radius: 50%;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .modal-close:hover {
            background: var(--bg-grey);
        }

        .modal-body {
            padding: 16px;
        }

        /* Media Player */
        .media-player video,
        .media-player audio {
            width: 100%;
            max-height: 70vh;
            background: #000;
        }

        /* Utility */
        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-medium);
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            color: var(--text-light);
        }

        .hidden {
            display: none !important;
        }

        /* Animation */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .animate-pulse {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-top: 72px;
            }

            .page-title {
                font-size: 20px;
            }

            .page-header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .view-toggle {
                align-self: flex-end;
            }

            .form-row {
                flex-direction: column;
            }

            .card-header {
                padding: 12px;
            }

            .card-body {
                padding: 12px;
            }

            .table th,
            .table td {
                padding: 10px 12px;
            }

            .file-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 12px;
            }

            .file-card-preview {
                height: 60px;
            }

            .file-card-body {
                padding: 10px;
            }

            .file-card-actions {
                margin-top: 10px;
                padding-top: 10px;
            }

            .btn {
                padding: 8px 12px;
                font-size: 12px;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 11px;
            }

            .tabs {
                margin-bottom: 16px;
            }

            .tab {
                padding: 10px 12px;
                font-size: 13px;
            }

            .list-item {
                flex-wrap: wrap;
                gap: 12px;
            }

            .list-item-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .file-list-item {
                flex-wrap: wrap;
            }

            .file-list-item-actions {
                width: 100%;
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid var(--divider);
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 12px;
                padding-top: 68px;
            }

            .file-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .file-card-name {
                font-size: 12px;
            }

            .badge {
                font-size: 10px;
                padding: 3px 6px;
            }
        }
    </style>
</head>

<body x-data="{ sidebarOpen: false }">
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="mobile-header-btn" @click="sidebarOpen = true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <span class="mobile-header-title">Downloader</span>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ 'open': sidebarOpen }">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <h1>Downloader</h1>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('downloads.index') }}"
                    class="nav-item {{ request()->routeIs('downloads.*') ? 'active' : '' }}"
                    @click="sidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Downloads
                </a>
                <a href="{{ route('files.index') }}"
                    class="nav-item {{ request()->routeIs('files.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    File Manager
                </a>
                <a href="{{ route('trash.index') }}"
                    class="nav-item {{ request()->routeIs('trash.*') ? 'active' : '' }}" @click="sidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Trash
                </a>
                <a href="{{ route('settings.index') }}"
                    class="nav-item {{ request()->routeIs('settings.*') || request()->routeIs('password.*') ? 'active' : '' }}"
                    @click="sidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </nav>

            <div class="sidebar-footer">
                @php
                    use App\Models\Download;
                    use App\Enums\DownloadStatus;

                    // Count only completed downloads from database (not trashed)
                    $completedDownloads = Download::where('status', DownloadStatus::COMPLETED)
                        ->whereNull('trashed_at')
                        ->get();
                    $fileCount = $completedDownloads->count();
                    $totalSize = $completedDownloads->sum('file_size');

                    // Count trashed items
                    $trashedItems = Download::whereNotNull('trashed_at')->get();
                    $trashCount = $trashedItems->count();
                    $trashSize = $trashedItems->sum('file_size');

                    if ($totalSize >= 1073741824) {
                        $formattedSize = number_format($totalSize / 1073741824, 2) . ' GB';
                    } elseif ($totalSize >= 1048576) {
                        $formattedSize = number_format($totalSize / 1048576, 2) . ' MB';
                    } elseif ($totalSize >= 1024) {
                        $formattedSize = number_format($totalSize / 1024, 2) . ' KB';
                    } else {
                        $formattedSize = $totalSize . ' B';
                    }

                    if ($trashSize >= 1073741824) {
                        $formattedTrashSize = number_format($trashSize / 1073741824, 2) . ' GB';
                    } elseif ($trashSize >= 1048576) {
                        $formattedTrashSize = number_format($trashSize / 1048576, 2) . ' MB';
                    } elseif ($trashSize >= 1024) {
                        $formattedTrashSize = number_format($trashSize / 1024, 2) . ' KB';
                    } else {
                        $formattedTrashSize = $trashSize . ' B';
                    }

                    $downloadPath = storage_path('app/downloads');
                    $diskFree = is_dir($downloadPath) ? disk_free_space($downloadPath) : 0;
                    $diskTotal = is_dir($downloadPath) ? disk_total_space($downloadPath) : 1;
                    $diskUsedPercent = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100) : 0;

                    if ($diskFree >= 1073741824) {
                        $formattedFree = number_format($diskFree / 1073741824, 1) . ' GB';
                    } else {
                        $formattedFree = number_format($diskFree / 1048576, 0) . ' MB';
                    }

                    if ($diskTotal >= 1073741824) {
                        $formattedTotal = number_format($diskTotal / 1073741824, 0) . ' GB';
                    } else {
                        $formattedTotal = number_format($diskTotal / 1048576, 0) . ' MB';
                    }
                @endphp

                <div class="storage-widget">
                    <div class="storage-label">Storage</div>
                    <div class="storage-size">{{ $formattedSize }}</div>
                    <div class="storage-count">{{ $fileCount }} {{ $fileCount == 1 ? 'file' : 'files' }}</div>
                    @if($trashCount > 0)
                        <div class="storage-count" style="color: var(--text-light); font-size: 11px;">
                            🗑️ {{ $trashCount }} in trash ({{ $formattedTrashSize }})
                        </div>
                    @endif
                    <div class="storage-bar">
                        <div class="storage-bar-fill" style="width: {{ $diskUsedPercent }}%;"></div>
                    </div>
                    <div class="storage-info">{{ $formattedFree }} free / {{ $formattedTotal }}</div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-item" style="color: var(--text-medium);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            @if(session('success'))
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>

</html>
@extends('layouts.app')

@section('title', 'Downloads - Web Downloader')

@section('content')
    <div x-data="downloadManager()" x-init="init()">
        <div class="page-header">
            <div class="page-header-row">
                <div>
                    <h1 class="page-title">Downloads</h1>
                    <p class="page-subtitle">Add URLs to download files in the background</p>
                </div>
                <div class="view-toggle">
                    <button class="view-toggle-btn" :class="{ 'active': viewMode === 'list' }" @click="viewMode = 'list'"
                        title="List View">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>
                    <button class="view-toggle-btn" :class="{ 'active': viewMode === 'grid' }" @click="viewMode = 'grid'"
                        title="Grid View">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Download Form -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('downloads.store') }}" method="POST">
                    @csrf
                    <div class="form-row">
                        <input type="url" name="url" class="form-input"
                            placeholder="https://example.com/file.zip or YouTube URL" value="{{ old('url') }}" required>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <span>Download</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Downloads List -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Queue</span>
                <span class="badge badge-blue" x-text="downloads.length + ' items'"></span>
            </div>

            <template x-if="downloads.length === 0">
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    <p>No downloads yet. Add a URL above to start!</p>
                </div>
            </template>

            <!-- List View -->
            <template x-if="downloads.length > 0 && viewMode === 'list'">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 150px;">Progress</th>
                                <th style="width: 80px;">Size</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="download in downloads" :key="download.id">
                                <tr>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <div class="text-truncate" style="font-weight: 500;"
                                                x-text="download.filename || truncateUrl(download.url)"></div>
                                            <div class="text-truncate" style="font-size: 12px; color: var(--text-medium);"
                                                x-text="download.url"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" :class="{
                                                            'badge-gray': download.status === 'queued',
                                                            'badge-blue': download.status === 'downloading',
                                                            'badge-green': download.status === 'completed',
                                                            'badge-red': download.status === 'failed'
                                                        }" x-text="download.status_label"></span>
                                    </td>
                                    <td>
                                        <template x-if="download.status === 'downloading' || download.status === 'queued'">
                                            <div>
                                                <div class="progress-bar">
                                                    <div class="progress-bar-fill"
                                                        :style="'width: ' + download.progress + '%'"></div>
                                                </div>
                                                <div style="font-size: 11px; color: var(--text-medium); margin-top: 4px;">
                                                    <span x-text="download.progress + '%'"></span>
                                                    <template x-if="download.status === 'downloading'">
                                                        <span class="animate-pulse"> ⏳</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="download.status === 'completed'">
                                            <span style="color: var(--success); font-size: 13px;">✓ Done</span>
                                        </template>
                                        <template x-if="download.status === 'failed'">
                                            <span style="color: var(--error); font-size: 11px;"
                                                x-text="'✕ ' + (download.error_message || 'Failed')"></span>
                                        </template>
                                    </td>
                                    <td x-text="download.formatted_size || '-'" style="font-size: 13px;"></td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <template x-if="download.status === 'completed' && isPlayable(download)">
                                                <a :href="'/stream/' + download.id" target="_blank"
                                                    class="btn btn-secondary btn-sm btn-icon" title="Play">▶</a>
                                            </template>
                                            <template x-if="download.status === 'completed'">
                                                <a :href="'/files/' + download.id + '/download'"
                                                    class="btn btn-secondary btn-sm btn-icon" title="Download">⬇</a>
                                            </template>
                                            <template x-if="download.status === 'failed'">
                                                <form :action="'/downloads/' + download.id + '/retry'" method="POST"
                                                    style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                                                        title="Retry">↻</button>
                                                </form>
                                            </template>
                                            <form :action="'/downloads/' + download.id" method="POST"
                                                style="display: inline;" x-ref="deleteForm">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm btn-icon" title="Remove"
                                                    @click="if(confirm('Hapus record ini dari daftar? (File tidak akan dihapus)')) $refs.deleteForm.submit()">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Grid View -->
            <template x-if="downloads.length > 0 && viewMode === 'grid'">
                <div class="card-body">
                    <div class="file-grid">
                        <template x-for="download in downloads" :key="download.id">
                            <div class="file-card">
                                <div class="file-card-preview">
                                    <template x-if="download.status === 'downloading'">
                                        <div style="text-align: center;">
                                            <div class="animate-pulse" style="font-size: 24px;">⏳</div>
                                            <div style="font-size: 14px; font-weight: 500; margin-top: 4px;"
                                                x-text="download.progress + '%'"></div>
                                        </div>
                                    </template>
                                    <template x-if="download.status === 'queued'">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="download.status === 'completed'">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" style="color: var(--success)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                    <template x-if="download.status === 'failed'">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" style="color: var(--error)">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </template>
                                </div>
                                <div class="file-card-body">
                                    <div class="file-card-name" x-text="download.filename || truncateUrl(download.url)">
                                    </div>
                                    <div class="file-card-meta">
                                        <span class="badge" :class="{
                                                            'badge-gray': download.status === 'queued',
                                                            'badge-blue': download.status === 'downloading',
                                                            'badge-green': download.status === 'completed',
                                                            'badge-red': download.status === 'failed'
                                                        }" x-text="download.status_label"></span>
                                        <span x-show="download.formatted_size"
                                            x-text="' • ' + download.formatted_size"></span>
                                    </div>
                                    <div class="file-card-actions">
                                        <template x-if="download.status === 'completed' && isPlayable(download)">
                                            <a :href="'/stream/' + download.id" target="_blank"
                                                class="btn btn-primary btn-sm">▶ Play</a>
                                        </template>
                                        <template x-if="download.status === 'completed'">
                                            <a :href="'/files/' + download.id + '/download'"
                                                class="btn btn-secondary btn-sm">⬇</a>
                                        </template>
                                        <template x-if="download.status === 'failed'">
                                            <form :action="'/downloads/' + download.id + '/retry'" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm">↻ Retry</button>
                                            </form>
                                        </template>
                                        <form :action="'/downloads/' + download.id" method="POST" x-ref="deleteFormGrid">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm"
                                                @click="if(confirm('Hapus record ini dari daftar? (File tidak akan dihapus)')) $el.closest('form').submit()">✕</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function downloadManager() {
            return {
                downloads: [],
                polling: null,
                viewMode: localStorage.getItem('downloadsViewMode') || 'list',

                init() {
                    this.fetchDownloads();
                    this.startPolling();
                    this.$watch('viewMode', (value) => localStorage.setItem('downloadsViewMode', value));
                },

                async fetchDownloads() {
                    try {
                        const response = await fetch('{{ route("downloads.statusAll") }}');
                        const data = await response.json();
                        this.downloads = data.downloads;
                    } catch (error) {
                        console.error('Failed to fetch downloads:', error);
                    }
                },

                startPolling() {
                    this.polling = setInterval(() => {
                        this.fetchDownloads();
                    }, 2000);
                },

                truncateUrl(url) {
                    if (url.length > 50) return url.substring(0, 50) + '...';
                    return url;
                },

                isPlayable(download) {
                    const mime = download.mime_type || '';
                    return mime.startsWith('video/') || mime.startsWith('audio/');
                }
            };
        }
    </script>
@endsection
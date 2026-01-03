@extends('layouts.app')

@section('title', 'File Manager - Web Downloader')

@section('content')
    <div x-data="fileManager()">
        <div class="page-header">
            <div class="page-header-row">
                <div>
                    <h1 class="page-title">File Manager</h1>
                    <p class="page-subtitle">Browse and manage your downloaded files</p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <input type="file" x-ref="fileInput" style="display: none;" @change="handleFileUpload($event)">
                    <button class="btn btn-primary" @click="$refs.fileInput.click()" :disabled="uploading">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" style="margin-right: 4px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <span x-text="uploading ? 'Uploading...' : 'Upload File'"></span>
                    </button>
                    <div class="view-toggle">
                        <button class="view-toggle-btn" :class="{ 'active': viewMode === 'list' }"
                            @click="viewMode = 'list'" title="List View">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </button>
                        <button class="view-toggle-btn" :class="{ 'active': viewMode === 'grid' }"
                            @click="viewMode = 'grid'" title="Grid View">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Progress Bar -->
        <div x-show="uploading" x-transition class="card"
            style="margin-bottom: 16px; border-left: 4px solid var(--primary);">
            <div class="card-body" style="padding: 12px 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 14px; font-weight: 500;" x-text="'Uploading: ' + uploadFilename"></span>
                    <span style="font-size: 14px; font-weight: 500;" x-text="uploadProgress + '%'"></span>
                </div>
                <div style="height: 8px; background: var(--divider); border-radius: 4px; overflow: hidden; position: relative;">
                    <div style="height: 100%; background: var(--primary); transition: width 0.3s ease-out;"
                        :style="{ width: uploadProgress + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="tabs">
            @foreach($categories as $key => $name)
                <a href="{{ route('files.index', ['category' => $key]) }}" class="tab {{ $category === $key ? 'active' : '' }}">
                    {{ $name }}
                    <span class="tab-count">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <!-- Empty State -->
        @if($files->isEmpty())
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                <p>No files in this category yet.</p>
            </div>
        @else
            <!-- Grid View -->
            <div class="file-grid" x-show="viewMode === 'grid'">
                @foreach($files as $file)
                    <div class="file-card">
                        <div class="file-card-preview">
                            @if($file->isVideo())
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($file->isAudio())
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                            @elseif(str_contains($file->mime_type ?? '', 'pdf'))
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            @elseif(str_contains($file->mime_type ?? '', 'zip') || str_contains($file->mime_type ?? '', 'rar'))
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="file-card-body">
                            <div class="file-card-name" title="{{ $file->filename }}">
                                {{ Str::limit($file->filename, 35) }}
                            </div>
                            <div class="file-card-meta">
                                {{ $file->formatted_size }} • {{ $file->completed_at?->format('M d') }}
                            </div>
                            <div class="file-card-actions">
                                @if($file->status === \App\Enums\DownloadStatus::PROCESSING)
                                    <span class="badge badge-purple" style="width: 100%; text-align: center;">Optimizing...</span>
                                @else
                                    @if($file->isPlayable())
                                        <div style="display: flex; gap: 4px;">
                                            <button type="button" class="btn btn-primary btn-sm"
                                                @click="openPlayer({{ $file->id }}, '{{ $file->filename }}', {{ $file->isVideo() ? 'true' : 'false' }})">
                                                ▶ Play
                                            </button>
                                            @if($file->isVideo())
                                                <form action="{{ route('files.optimize', $file) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary btn-sm" title="Optimize for Web">✨</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                    <a href="{{ route('files.download', $file) }}" class="btn btn-secondary btn-sm" title="Download">⬇</a>
                                @endif
                                <form id="delete-form-grid-{{ $file->id }}" action="{{ route('files.destroy', $file) }}"
                                    method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger btn-sm"
                                        onclick="confirmTrash({{ $file->id }}, 'grid')">✕</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- List View -->
            <div class="file-list" x-show="viewMode === 'list'">
                @foreach($files as $file)
                    <div class="file-list-item">
                        <div class="file-list-item-icon">
                            @if($file->isVideo())
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($file->isAudio())
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            @endif
                        </div>
                        <div class="file-list-item-content">
                            <div class="file-list-item-name" title="{{ $file->filename }}">{{ $file->filename }}</div>
                            <div class="file-list-item-meta">{{ $file->formatted_size }} •
                                {{ $file->completed_at?->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="file-list-item-actions">
                            @if($file->status === \App\Enums\DownloadStatus::PROCESSING)
                                <span class="badge badge-purple">Optimizing...</span>
                            @else
                                @if($file->isPlayable())
                                    <button type="button" class="btn btn-primary btn-sm"
                                        @click="openPlayer({{ $file->id }}, '{{ $file->filename }}', {{ $file->isVideo() ? 'true' : 'false' }})">
                                        ▶ Play
                                    </button>
                                    @if($file->isVideo())
                                        <form action="{{ route('files.optimize', $file) }}" method="POST" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Optimize for Web">✨ Optimize</button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ route('files.download', $file) }}" class="btn btn-secondary btn-sm">⬇ Download</a>
                            @endif
                            <form id="delete-form-list-{{ $file->id }}" action="{{ route('files.destroy', $file) }}" method="POST"
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-danger btn-sm"
                                    onclick="confirmTrash({{ $file->id }}, 'list')">✕</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Media Player Modal -->
        <template x-if="playerOpen">
            <div class="modal-backdrop" @click.self="closePlayer()">
                <div class="modal">
                    <div class="modal-header">
                        <h3 class="modal-title" x-text="playerFilename"></h3>
                        <button class="modal-close" @click="closePlayer()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body media-player">
                        <template x-if="playerIsVideo">
                            <video :src="'/stream/' + playerId" controls autoplay></video>
                        </template>
                        <template x-if="!playerIsVideo">
                            <audio :src="'/stream/' + playerId" controls autoplay style="width: 100%;"></audio>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function fileManager() {
            return {
                viewMode: localStorage.getItem('filesViewMode') || 'grid',
                playerOpen: false,
                playerId: null,
                playerFilename: '',
                playerIsVideo: true,

                // Upload state
                uploading: false,
                uploadProgress: 0,
                uploadFilename: '',

                init() {
                    this.$watch('viewMode', (value) => localStorage.setItem('filesViewMode', value));
                    
                    // Safeguard: warn before leaving page while uploading
                    this.$watch('uploading', (value) => {
                        if (value) {
                            window.onbeforeunload = function() {
                                return "Upload sedang berjalan. Jika Anda keluar, upload akan terhenti. Yakin ingin keluar?";
                            };
                        } else {
                            window.onbeforeunload = null;
                        }
                    });
                },

                handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.uploading = true;
                    this.uploadProgress = 0;
                    this.uploadFilename = file.name;

                    const formData = new FormData();
                    formData.append('file', file);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '{{ route("files.upload") }}', true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                    // Progress events
                    xhr.upload.onprogress = (e) => {
                        if (e.lengthComputable) {
                            this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                        }
                    };

                    xhr.onload = () => {
                        if (xhr.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'File ' + this.uploadFilename + ' berhasil diupload.',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            let errorMsg = 'Gagal mengupload file.';
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMsg = response.error || response.message || errorMsg;
                            } catch (e) { }

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: errorMsg
                            });
                            this.uploading = false;
                        }
                    };

                    xhr.onerror = () => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan jaringan.'
                        });
                        this.uploading = false;
                    };

                    xhr.send(formData);

                    // Reset input so the same file can be uploaded again if needed
                    event.target.value = '';
                },

                openPlayer(id, filename, isVideo) {
                    this.playerId = id;
                    this.playerFilename = filename;
                    this.playerIsVideo = isVideo;
                    this.playerOpen = true;
                },

                closePlayer() {
                    this.playerOpen = false;
                    this.playerId = null;
                }
            };
        }

        function confirmTrash(id, viewType) {
            Swal.fire({
                title: 'Pindah ke Trash?',
                text: 'File akan dipindah ke Recycle Bin dan dihapus otomatis setelah 30 hari.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Pindahkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + viewType + '-' + id).submit();
                }
            });
        }
    </script>
@endsection
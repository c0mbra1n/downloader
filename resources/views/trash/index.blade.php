@extends('layouts.app')

@section('title', 'Trash - Web Downloader')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">🗑️ Recycle Bin</h1>
            <p class="page-subtitle">Items will be permanently deleted after 30 days</p>
        </div>
        @if($trashedItems->count() > 0)
            <form id="empty-trash-form" action="{{ route('trash.empty') }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="confirmEmptyTrash()">
                    🗑️ Empty Trash ({{ $formattedSize }})
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        @if($trashedItems->count() === 0)
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <h3>Trash is empty</h3>
                <p>Deleted files will appear here</p>
            </div>
        @else
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Deleted At</th>
                                <th>Auto Delete In</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trashedItems as $item)
                                <tr>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;" title="{{ $item->filename }}">
                                            {{ $item->filename }}
                                        </div>
                                    </td>
                                    <td style="white-space: nowrap;">{{ $item->formatted_size }}</td>
                                    <td style="white-space: nowrap;">{{ $item->trashed_at?->format('M d, H:i') ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $item->days_until_deletion <= 7 ? 'badge-red' : 'badge-gray' }}">
                                            {{ $item->days_until_deletion }} days
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 4px;">
                                            <form action="{{ route('trash.restore', $item) }}" method="POST"
                                                style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm" title="Restore">
                                                    ↩️ Restore
                                                </button>
                                            </form>
                                            <form id="delete-form-{{ $item->id }}" action="{{ route('trash.destroy', $item) }}"
                                                method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm" title="Delete Forever"
                                                    onclick="confirmPermanentDelete({{ $item->id }})">
                                                    ✕
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <script>
        function confirmEmptyTrash() {
            Swal.fire({
                title: 'Kosongkan Trash?',
                text: 'Semua file akan dihapus permanen. Tindakan ini tidak bisa dibatalkan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Kosongkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('empty-trash-form').submit();
                }
            });
        }

        function confirmPermanentDelete(id) {
            Swal.fire({
                title: 'Hapus Permanen?',
                text: 'File akan dihapus selamanya dan tidak bisa dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endsection
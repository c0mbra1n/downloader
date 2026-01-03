@extends('layouts.app')

@section('title', 'Settings - Web Downloader')

@section('content')
    <div class="page-header">
        <h1 class="page-title">⚙️ Settings</h1>
        <p class="page-subtitle">Manage application settings</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <!-- Change Password Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 style="margin: 0; font-size: 16px;">🔐 Ubah Password</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('password.update') }}" method="POST" style="max-width: 400px;">
                @csrf

                <div class="form-group">
                    <label for="current_password" class="form-label">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password"
                        class="form-input @error('current_password') error @enderror" required>
                    @error('current_password')
                        <div style="color: var(--error); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" id="password" name="password"
                        class="form-input @error('password') error @enderror" required>
                    @error('password')
                        <div style="color: var(--error); font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-input"
                        required>
                </div>

                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </form>
        </div>
    </div>

    <!-- Cookies Card -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 style="margin: 0; font-size: 16px;">🍪 Browser Cookies</h3>
                <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-medium);">
                    Untuk download dari situs yang memerlukan login (YouTube, dll)
                </p>
            </div>
            @if($cookiesExists)
                <span class="badge badge-green">✓ Aktif</span>
            @else
                <span class="badge badge-gray">Tidak ada</span>
            @endif
        </div>
        <div class="card-body">
            <form id="cookies-form" action="{{ route('settings.cookies') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="cookies" class="form-label">
                        Cookies Content (Format Netscape)
                    </label>
                    <textarea id="cookies" name="cookies" class="form-input" rows="10" placeholder="# Netscape HTTP Cookie File
    # Export cookies dari browser menggunakan extension 'Get cookies.txt LOCALLY'
    # Gabungkan cookies dari berbagai situs ke dalam file ini

    .youtube.com	TRUE	/	TRUE	1234567890	COOKIE_NAME	COOKIE_VALUE"
                        style="font-family: monospace; font-size: 12px;">{{ $cookiesContent }}</textarea>
                    <div style="color: var(--text-medium); font-size: 12px; margin-top: 4px;">
                        Cara export: Install extension "Get cookies.txt LOCALLY" → Login ke situs → Export → Paste di sini
                    </div>
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary">Simpan Cookies</button>
                    @if($cookiesExists)
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteCookies()">
                            Hapus Cookies
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmDeleteCookies() {
            Swal.fire({
                title: 'Hapus Cookies?',
                text: 'Semua cookies akan dihapus. Download dari situs yang butuh login mungkin tidak berfungsi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cookies').value = '';
                    document.getElementById('cookies-form').submit();
                }
            });
        }
    </script>
@endsection
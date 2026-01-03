@extends('layouts.app')

@section('title', 'Ubah Password - Web Downloader')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Ubah Password</h1>
        <p class="page-subtitle">Ganti password akun Anda</p>
    </div>

    <div class="card" style="max-width: 500px;">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom: 16px;">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('password.update') }}" method="POST">
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
@endsection
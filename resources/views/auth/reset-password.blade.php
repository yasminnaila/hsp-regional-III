@extends('auth.password-layout')

@section('title', 'Password Baru - HSP Regional III')

@section('form')
    <h1>Buat password baru</h1>
    <p>Gunakan password minimal 8 karakter untuk menjaga keamanan akun Anda.</p>

    <form method="POST" action="{{ route('password.update') }}" class="stack login-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email
            <span class="login-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
            </span>
        </label>
        <label>Password baru
            <span class="login-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" name="password" required>
            </span>
        </label>
        <label>Konfirmasi password
            <span class="login-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                <input type="password" name="password_confirmation" required>
            </span>
        </label>
        @if ($errors->any())<div class="alert danger">{{ $errors->first() }}</div>@endif
        <button class="btn primary login-submit" type="submit">Simpan password baru</button>
    </form>
@endsection

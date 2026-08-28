@extends('auth.password-layout')

@section('title', 'Lupa Password - HSP Regional III')

@section('form')
    <h1>Atur ulang password</h1>
    <p>Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur password baru.</p>

    @if (session('status'))
        <div class="alert success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="stack login-form">
        @csrf
        <label>Email
            <span class="login-input-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </span>
        </label>
        @error('email')<div class="alert danger">{{ $message }}</div>@enderror
        <button class="btn primary login-submit" type="submit">Kirim tautan reset</button>
    </form>
    <a class="login-back-link" href="{{ route('login') }}">Kembali ke halaman masuk</a>
@endsection

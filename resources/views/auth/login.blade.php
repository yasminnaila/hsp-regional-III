<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HSP Regional III</title>
    <link rel="stylesheet" href="/css/hsp.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="brand-mark">HSP</div>
        <h1>Selamat Datang</h1>
        <p>Masuk ke sistem HSP Regional III</p>

        @if ($errors->any())
            <div class="alert danger fade-in" style="text-align:left">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="stack">
            @csrf
            <label style="font-size:13px;font-weight:500;">
                Username
                <input type="text" name="username" value="{{ old('username') }}" required autofocus style="margin-top:4px;">
            </label>
            <label style="font-size:13px;font-weight:500;">
                Password
                <input type="password" name="password" required style="margin-top:4px;">
            </label>
            <label class="check" style="font-size:13px;gap:6px;">
                <input type="checkbox" name="remember" value="1"> Ingat saya
            </label>
            <button class="btn primary" type="submit" style="padding:11px;font-size:15px;">Masuk</button>
        </form>
    </main>
</body>
</html>

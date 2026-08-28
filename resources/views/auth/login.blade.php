<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HSP Regional III</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/hsp.css') }}?v={{ filemtime(public_path('css/hsp.css')) }}">
</head>
<body class="login-page">
    <svg class="login-backdrop" viewBox="0 0 900 640" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <radialGradient id="login-haze" cx="0" cy="0" r="1" gradientTransform="translate(60 40) rotate(42) scale(260 180)" gradientUnits="userSpaceOnUse"><stop stop-color="#fca5a5" stop-opacity=".42"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient>
            <linearGradient id="login-coral" x1="590" y1="640" x2="900" y2="350" gradientUnits="userSpaceOnUse"><stop stop-color="#e31b23" stop-opacity=".24"/><stop offset="1" stop-color="#fb7185" stop-opacity=".06"/></linearGradient>
            <pattern id="login-dots" width="12" height="12" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.1" fill="#94a3b8" fill-opacity=".3"/></pattern>
        </defs>
        <rect width="900" height="640" fill="#fff"/><rect width="900" height="640" fill="url(#login-haze)"/>
        <rect x="760" y="26" width="110" height="110" fill="url(#login-dots)" opacity=".72"/>
        <g class="login-backdrop-lines">
            <path d="M0 500H306M40 500V170H236M40 188L236 170M40 170L70 142L100 170M70 170V302M100 170L236 242M130 170L236 314M160 170L236 386M236 170V255M224 255H248M236 255V308"/>
            <path d="M58 500V310L120 272L182 310V500M120 272V500M58 352L120 314L182 352M58 394L120 356L182 394M58 436L120 398L182 436M58 478L120 440L182 478"/>
            <path d="M202 500V348L250 319L298 348V500M250 319V500M202 385L250 356L298 385M202 422L250 393L298 422M202 459L250 430L298 459"/>
            <path d="M654 500V188L755 126L856 188V500M755 126V500M654 247L755 185L856 247M654 311L755 249L856 311M654 375L755 313L856 375M654 439L755 377L856 439"/>
            <path d="M676 500V160M700 500V145M724 500V130M748 500V115M772 500V138M796 500V155M820 500V172" stroke-opacity=".14"/>
            <path d="M613 500H878M628 514H866M642 528H853" stroke-opacity=".14"/>
        </g>
        <g class="login-backdrop-detail">
            <path d="M40 170H236M40 170L236 242M64 170L236 278M88 170L236 314M112 170L236 350M136 170L236 386"/>
            <path d="M58 310H182M58 331H182M58 352H182M58 373H182M58 394H182M58 415H182M58 436H182M58 457H182"/>
            <path d="M74 310V500M92 310V500M110 310V500M128 310V500M146 310V500M164 310V500"/>
            <path d="M604 500L689 450L774 500M604 470L689 420L774 470M604 440L689 390L774 440M604 410L689 360L774 410M604 380L689 330L774 380"/>
            <path d="M622 500V310M644 500V296M666 500V282M688 500V268M710 500V282M732 500V296M754 500V310"/>
            <path d="M799 500V346M816 500V336M833 500V326M850 500V336M867 500V346"/>
            <path d="M28 532H302M28 540H302M574 548H878M574 556H878"/>
            <path d="M282 280H360M282 274V286M360 274V286M804 155H868M804 149V161M868 149V161"/>
        </g>
        <path d="M0 565L320 640H0Z" fill="#cbd5e1" fill-opacity=".12"/>
        <path d="M492 640L900 360V640Z" fill="url(#login-coral)"/>
        <path d="M560 640L900 420V640Z" fill="#e31b23" fill-opacity=".08"/>
        <path d="M0 610H310M585 570H875" class="login-backdrop-faint"/>
    </svg>
    <main class="login-card">
        <header class="login-intro">
            <div class="login-brand-row"><div class="brand-mark">HSP</div><span>Regional III</span></div>
            <h1>Selamat datang</h1>
            <p>Masuk untuk mengakses sistem HSP Regional III.</p>
        </header>

        @if ($errors->any())
            <div class="alert danger fade-in" style="text-align:left">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="stack login-form">
            @csrf
            <label>
                Username
                <span class="login-input-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.5 3.1-5.3 7-5.3s6.2 1.8 7 5.3"/></svg>
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus>
                </span>
            </label>
            <label>
                Password
                <span class="login-input-wrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <input type="password" name="password" required>
                    <svg class="login-eye" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.4-5 9.5-5 9.5 5 9.5 5-3.4 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.2"/></svg>
                </span>
            </label>
            <div class="login-options"><label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa password?</a></div>
            <button class="btn primary login-submit" type="submit">Masuk</button>
        </form>
        <footer class="login-footer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10l4-3 4 3V4l4 3v13M2 20h20M8 13h1M8 16h1M15 11h1M15 15h1"/></svg>Harga Satuan Pekerjaan Konstruksi</footer>
    </main>
</body>
</html>

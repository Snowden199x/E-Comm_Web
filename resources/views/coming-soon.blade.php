<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coming Soon — Vendo</title>
    <style>
        body { display:flex; align-items:center; justify-content:center; height:100vh; margin:0; font-family:sans-serif; background:#0f0f0f; color:#fff; text-align:center; }
        h1 { font-size:2rem; }
    </style>
</head>
<body>
    <div>
    <h1>{{ $title ?? 'Coming Soon' }}</h1>
    <p>We're working on this. Check back soon.</p>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 1rem;">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>
</body>
</html>
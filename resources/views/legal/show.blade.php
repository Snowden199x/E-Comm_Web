<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Vendo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/shared/app.css', 'resources/css/shared/legal.css'])
</head>

<body class="legal-page">
    <a class="legal-skip" href="#legal-content">Skip to content</a>

    <header class="legal-header">
        <div class="legal-header__inner">
            <a class="legal-brand" href="{{ url('/') }}" aria-label="Vendo home">
                <img src="{{ asset('images/logo/vendo-full.png') }}" alt="Vendo">
            </a>
            <nav class="legal-nav" aria-label="Legal pages">
                <a href="{{ route('legal.terms') }}" @if (request()->routeIs('legal.terms')) aria-current="page" @endif>Terms</a>
                <a href="{{ route('legal.privacy') }}" @if (request()->routeIs('legal.privacy')) aria-current="page" @endif>Privacy</a>
            </nav>
        </div>
    </header>

    <main id="legal-content" class="legal-main">
        <article class="legal-content">
            {!! $content !!}
        </article>
    </main>

    <footer class="legal-footer">
        <div class="legal-footer__inner">
            <span>© {{ date('Y') }} Vendo</span>
            <a href="mailto:vendoapp.official@gmail.com">vendoapp.official@gmail.com</a>
        </div>
    </footer>
</body>

</html>

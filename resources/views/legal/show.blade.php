<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.site-icon')
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
            <div class="legal-header__lead">
                <button type="button" id="legalBack" class="legal-back" data-home="{{ $homeUrl }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5 8 12l7 7" /></svg>
                    Back
                </button>
                <a class="legal-brand" href="{{ $homeUrl }}" aria-label="Vendo home">
                    <img src="{{ asset('images/logo/vendo-full.png') }}" alt="Vendo">
                </a>
            </div>
            <nav class="legal-nav" aria-label="Legal pages">
                <a href="{{ route('legal.terms') }}" @if (request()->routeIs('legal.terms')) aria-current="page" @endif>Terms</a>
                <a href="{{ route('legal.privacy') }}" @if (request()->routeIs('legal.privacy')) aria-current="page" @endif>Privacy</a>
            </nav>
        </div>
    </header>

    <main id="legal-content" class="legal-main">
        <p class="legal-eyebrow">Vendo legal</p>

        <div class="legal-layout">
            @if (! empty($sections))
                <aside class="legal-toc" aria-label="On this page">
                    <p class="legal-toc__title">On this page</p>
                    <ol>
                        @foreach ($sections as $section)
                            <li><a href="#{{ $section['id'] }}">{{ $section['label'] }}</a></li>
                        @endforeach
                    </ol>
                    <p class="legal-toc__updated">Last updated {{ $updatedAt }}</p>
                </aside>
            @endif

            <article class="legal-content">
                {!! $content !!}
            </article>
        </div>
    </main>

    <footer class="legal-footer">
        <div class="legal-footer__inner">
            <span>© {{ date('Y') }} Vendo</span>
            <div class="legal-footer__links">
                <button type="button" class="legal-back legal-back--footer" data-home="{{ $homeUrl }}" onclick="legalGoBack(this)">Back</button>
                <a href="mailto:vendoapp.official@gmail.com">vendoapp.official@gmail.com</a>
            </div>
        </div>
    </footer>

    <script>
        function legalGoBack(trigger) {
            if (document.referrer !== '' && window.history.length > 1) {
                window.history.back();
                return;
            }
            window.location.href = trigger.dataset.home;
        }
        document.getElementById('legalBack').addEventListener('click', function () {
            legalGoBack(this);
        });
    </script>
</body>

</html>

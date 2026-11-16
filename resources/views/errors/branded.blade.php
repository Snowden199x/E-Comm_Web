<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/Web_Logo.png') }}">
    <title>{{ $code }} — {{ $heading }} | Vendo</title>

    <style>
        :root {
            color-scheme: dark;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {
            min-height: 100dvh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(24px, 6vw, 72px) 20px;
            color: #fff;
            text-align: center;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 50% 0%, rgba(218, 197, 130, .16), transparent 40%),
                radial-gradient(circle at 8% 92%, rgba(192, 94, 65, .16), transparent 35%),
                #3b1735;
        }

        .error-page {
            width: 100%;
            max-width: 620px;
        }

        .error-logo {
            display: block;
            width: clamp(180px, 32vw, 280px);
            height: auto;
            margin: 0 auto clamp(24px, 5vh, 44px);
        }

        .error-code {
            margin: 0;
            color: #dac582;
            font-size: clamp(72px, 15vw, 132px);
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            letter-spacing: .02em;
            line-height: .9;
        }

        .error-rule {
            width: 56px;
            height: 2px;
            margin: clamp(20px, 4vh, 30px) auto;
            border: 0;
            background: rgba(218, 197, 130, .55);
        }

        .error-heading {
            margin: 0;
            font-size: clamp(22px, 3.2vw, 30px);
            font-weight: 600;
            letter-spacing: -.01em;
            line-height: 1.25;
        }

        .error-message {
            max-width: 46ch;
            margin: 14px auto 0;
            color: rgba(255, 255, 255, .72);
            font-size: clamp(14px, 1.8vw, 16px);
            line-height: 1.7;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: clamp(28px, 5vh, 40px);
        }

        .error-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 24px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: #dac582;
            color: #3b1735;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .error-button:hover {
            background: #e7d59d;
            transform: translateY(-1px);
        }

        .error-button:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 4px;
        }

        .error-button--ghost {
            border-color: rgba(255, 255, 255, .28);
            background: transparent;
            color: #fff;
        }

        .error-button--ghost:hover {
            background: rgba(255, 255, 255, .08);
        }

        .error-support {
            margin: 22px 0 0;
            color: rgba(255, 255, 255, .5);
            font-size: 12px;
            line-height: 1.7;
        }

        .error-support a {
            color: rgba(255, 255, 255, .78);
            text-underline-offset: 3px;
        }

        @media (prefers-reduced-motion: reduce) {
            .error-button {
                transition: none;
            }
        }

        @media (max-width: 420px) {
            .error-actions {
                flex-direction: column;
            }

            .error-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="error-page">
        <img class="error-logo" src="{{ asset('assets/branding/log-in-logo.svg') }}" alt="Vendo">

        <p class="error-code" aria-hidden="true">{{ $code }}</p>
        <hr class="error-rule">

        <h1 class="error-heading">{{ $heading }}</h1>
        <p class="error-message">{{ $message }}</p>

        <div class="error-actions">
            <a class="error-button" href="{{ url('/') }}">Back to Vendo</a>
            <a class="error-button error-button--ghost" href="{{ url('/login') }}">Sign in</a>
        </div>

        @if ($code === 500)
            <p class="error-support">
                If this keeps happening, email
                <a href="mailto:vendoapp.official@gmail.com">vendoapp.official@gmail.com</a>.
            </p>
        @endif
    </main>
</body>

</html>

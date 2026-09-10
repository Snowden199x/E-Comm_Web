<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendo — Find What You Love. Vendo It.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/vendo.css', 'resources/js/vendo.js'])
</head>

<body>
    <div class="scroll-progress" id="scrollProgress"></div>

    @include('buyer.partials.nav')

    <main id="top">
        @include('buyer.partials.hero')
        @include('buyer.partials.categories')
        @include('buyer.partials.featured')
        @include('buyer.partials.why-vendo')
        @include('buyer.partials.how-it-works')
        @include('buyer.partials.showcase')
        @include('buyer.partials.order-tracking')
        @include('buyer.partials.trust')
        @include('buyer.partials.final-cta')
    </main>

    @include('buyer.partials.footer')
    @include('buyer.partials.toast')

</body>

</html>
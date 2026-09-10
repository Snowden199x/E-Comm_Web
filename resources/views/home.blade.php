<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vendo — Find What You Love. Vendo It.</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/vendo.css', 'resources/js/vendo.js'])
</head>
<body>
<div class="scroll-progress" id="scrollProgress"></div>

@include('buyer.partial.nav')

<main id="top">
  @include('buyer.partial.hero')
  @include('buyer.partial.categories')
  @include('buyer.partial.featured')
  @include('buyer.partial.why-vendo')
  @include('buyer.partial.how-it-works')
  @include('buyer.partial.showcase')
  @include('buyer.partial.order-tracking')
  @include('buyer.partial.trust')
  @include('buyer.partial.final-cta')
</main>

@include('buyer.partial.footer')
@include('buyer.partial.toast')

</body>
</html>
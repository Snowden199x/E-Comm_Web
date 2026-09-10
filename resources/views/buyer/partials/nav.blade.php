
<div class="scroll-progress" id="scrollProgress"></div>

<header id="siteHeader">
  <nav class="navbar">
    <a href="#top" class="logo-mark">
      <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="Vendo" class="logo-icon">
      Vendo
    </a>
    <ul class="nav-links">
      <li><a href="#top">Home</a></li>
      <li><a href="#categories">Categories</a></li>
      <li><a href="#how-it-works">How It Works</a></li>
      <li><a href="#trust">About Vendo</a></li>
    </ul>
    <div class="nav-right">
      <div class="search-box" id="searchBox">
        <input type="text" placeholder="Search products…">
      </div>
      <button class="icon-btn" id="searchToggle" aria-label="Search">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
      <button class="icon-btn" aria-label="Cart">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L4 3H2"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
        <span class="cart-count" id="cartCount">0</span>
      </button>
      <a href="{{ url('/buyer/login') }}" class="login-link">Log in</a>
      <a href="#" class="btn btn-primary">Get Started</a>
      <button class="hamburger" id="hamburgerBtn" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </nav>
</header>

<div class="mobile-panel" id="mobilePanel">
  <a href="#top">Home</a>
  <a href="#categories">Categories</a>
  <a href="#how-it-works">How It Works</a>
  <a href="#trust">About Vendo</a>
  <a href="#" class="btn btn-primary" style="align-self:flex-start;font-size:16px;">Get Started</a>
</div>


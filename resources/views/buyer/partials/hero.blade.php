<!-- HERO -->
  <section class="hero">
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>
    <div class="hero-blob hero-blob-3"></div>

    <div class="hero-intro wrap" style="padding:0;">
      <img src="{{ asset('images/logo/vendo-icon.png') }}" alt="Vendo" class="intro-icon">
      <span class="brand-word">VENDO</span>
      <span class="intro-tagline">Buy. Sell. Delivered.</span>
      <button class="scroll-cue" onclick="document.getElementById('heroContent').scrollIntoView({behavior:'smooth'})" aria-label="Scroll down">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
      </button>
    </div>

    <div class="hero-grid wrap" id="heroContent" style="padding:0;">
      <div class="hero-copy reveal-stagger">
        <span class="eyebrow">The buyer marketplace</span>
        <h1>Find What You Love.<br>Vendo It.</h1>
        <p class="lede">Discover products from trusted sellers, shop with ease, and keep track of your order from checkout to your doorstep.</p>
        <div class="hero-ctas">
          <a href="#featured" class="btn btn-primary">Start Shopping</a>
          <a href="#categories" class="btn btn-ghost">Explore Categories</a>
        </div>
      </div>
      <div class="hero-visual reveal">
        <div class="hero-visual-inner" id="heroVisualInner">
        <div class="float-card vc-search">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          Search "ceramic mug"
        </div>
        <div class="float-card vc-product">
          <div class="thumb">
            <span class="badge">-20%</span>
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4h16v12H4z"/><path d="M8 20h8"/><path d="M4 8h16"/></svg>
          </div>
          <div class="info">
            <div class="name">Woven Tote Bag</div>
            <div class="vc-rating"><span class="stars">★★★★★</span> 4.8 (212)</div>
            <div class="price-row"><span class="price">₱649</span><span class="old-price">₱810</span></div>
          </div>
        </div>
        <div class="float-card vc-bag">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 7h12l1 13H5z"/><path d="M9 7a3 3 0 016 0"/></svg>
        </div>
        <div class="float-card vc-cart">
          <div class="cart-head">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L4 3H2"/></svg>
            2 items in cart
          </div>
          <div class="cart-item">
            <div class="swatch s1"></div>
            <div class="ci-info">Linen Shirt<br><span style="color:var(--purple);font-weight:600;">₱890</span></div>
          </div>
          <div class="cart-item">
            <div class="swatch s2"></div>
            <div class="ci-info">Sunset Candle<br><span style="color:var(--purple);font-weight:600;">₱295</span></div>
          </div>
        </div>
        <div class="float-card vc-track">
          <div class="icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="13" height="7"/><path d="M16 13h3l2 3v1h-5z"/><circle cx="7.5" cy="19.5" r="1.5"/><circle cx="17.5" cy="19.5" r="1.5"/></svg>
          </div>
          <div>
            <div class="t-title">Out for delivery</div>
            <div class="t-sub">Arriving today, 2–5 PM</div>
          </div>
        </div>
        </div>
      </div>
    </div>
  </section>
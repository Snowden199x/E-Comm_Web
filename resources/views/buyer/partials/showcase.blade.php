<!-- SHOWCASE -->
  <section class="section">
    <div class="wrap">
      <div class="section-head reveal">
        <span class="eyebrow">Product page</span>
        <h2>Everything you need before you buy.</h2>
      </div>
      <div class="showcase reveal-scale">
        <div class="showcase-gallery">
          <div class="gallery-thumbs">
            <div class="gt active" data-full="{{ asset('images/showcase/tote-1.jpg') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 4h16v12H4z"/></svg></div>
            <div class="gt" data-full="{{ asset('images/showcase/tote-2.jpg') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8"/></svg></div>
            <div class="gt" data-full="{{ asset('images/showcase/tote-3.jpg') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="5" width="14" height="14" rx="3"/></svg></div>
          </div>
          <div class="gallery-main" id="galleryMain">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M4 4h16v12H4z"/><path d="M8 20h8M4 8h16"/></svg>
            <img id="galleryMainImg" src="{{ asset('images/showcase/tote-1.jpg') }}" alt="Handwoven Market Tote" loading="lazy" onerror="this.remove()">
          </div>
        </div>
        <div class="showcase-details">
          <div class="seller"><span class="seller-dot"></span> Sold by Casa Textiles · Verified Seller</div>
          <h3>Handwoven Market Tote</h3>
          <div class="prating"><span class="stars">★★★★★</span> 4.8 · 312 ratings</div>
          <div class="sprice">
            <span class="now">₱649</span>
            <span class="was">₱765</span>
            <span class="off">-15%</span>
          </div>
          <div class="opt-label">Color</div>
          <div class="swatches">
            <div class="swatch-opt active" style="background:var(--deep);"></div>
            <div class="swatch-opt" style="background:var(--terracotta);"></div>
            <div class="swatch-opt" style="background:var(--gold);"></div>
          </div>
          <div class="opt-label">Size</div>
          <div class="size-opts">
            <div class="size-opt active">Small</div>
            <div class="size-opt">Medium</div>
            <div class="size-opt">Large</div>
          </div>
          <div class="opt-label" style="margin-bottom:0;">Quantity</div>
          <div class="qty-row">
            <div class="qty-stepper">
              <button id="qtyMinus">−</button>
              <span id="qtyVal">1</span>
              <button id="qtyPlus">+</button>
            </div>
          </div>
          <div class="showcase-ctas">
            <button class="btn btn-ghost" id="showcaseAdd">Add to Cart</button>
            <a href="#" class="btn btn-primary">Buy Now</a>
          </div>
        </div>
      </div>
    </div>
  </section>


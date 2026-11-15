<!-- GET THE APP -->
  <section class="section" id="get-app" style="padding-top:20px;">
    <div class="wrap">
      <div class="app-band reveal">
        <div class="app-copy">
          <span class="eyebrow">Shop on the go</span>
          <h2>Get the Vendo app.</h2>
          <p>Browse products, place orders and follow your delivery from your phone. Scan the code with your phone camera to download the app.</p>
          {{-- The QR below is a fixed image (public/assets/buyer/buyer-app-qr.png) that points to the same address as this button. --}}
          <div class="cta-btns" style="justify-content:flex-start;margin-top:24px;">
            <a href="https://vendo-ph.app/get-the-app" class="btn btn-primary" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M11 18h2"/></svg>
              Get the Vendo app
            </a>
          </div>
        </div>
        <figure class="app-qr">
          <div class="app-qr-card">
            <img src="{{ asset('assets/buyer/buyer-app-qr.png') }}" alt="QR code to get the Vendo app" width="140" height="140" loading="lazy">
          </div>
          <figcaption>Scan to download the Vendo app</figcaption>
        </figure>
      </div>
    </div>
  </section>


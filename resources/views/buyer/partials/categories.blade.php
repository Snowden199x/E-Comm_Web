<section class="categories-section section" id="categories">
    <div class="wrap">

    <div class="scroller-controls">
        <div class="section-head reveal" style="margin-bottom:0;">
            <span class="eyebrow">Shop by category</span>
            <h2>Find what you need</h2>
        </div>

        <div class="cat-controls">
            <a href="{{ route('buyer.login') }}" class="view-all-link">
                View All
                <span>→</span>
            </a>
            <div class="arrow-btns">
                <button class="arrow-btn" id="catScrollLeft" aria-label="Scroll categories left">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button class="arrow-btn" id="catScrollRight" aria-label="Scroll categories right">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="cat-scroll-wrap">
    <div class="cat-grid" id="catScroller">

        {{-- 1. Pet Supplies --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/pet-and-supplies.jpg') }}"
                     alt="Pet Supplies">
            </div>
            <span>Pet Supplies</span>
        </a>

        {{-- 2. Electronics and Gadgets --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/electronics-and-gadgets.jpg') }}"
                     alt="Electronics and Gadgets">
            </div>
            <span>Electronics &amp; Gadgets</span>
        </a>

        {{-- 3. Women's Apparel --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset("images/categories/women's-apparel.jpg") }}"
                     alt="Women's Apparel">
            </div>
            <span>Women's Apparel</span>
        </a>

        {{-- 4. Men's Apparel --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset("images/categories/men's-apparel.jpg") }}"
                     alt="Men's Apparel">
            </div>
            <span>Men's Apparel</span>
        </a>

        {{-- 5. Kids and Baby --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/kids-and-baby.jpg') }}"
                     alt="Kids and Baby">
            </div>
            <span>Kids &amp; Baby</span>
        </a>

        {{-- 6. Home and Garden --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/home-and-garden.jpg') }}"
                     alt="Home and Garden">
            </div>
            <span>Home &amp; Garden</span>
        </a>

        {{-- 7. Sports and Outdoors --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/sports-and-outdoor.jpg') }}"
                     alt="Sports and Outdoors">
            </div>
            <span>Sports &amp; Outdoors</span>
        </a>

        {{-- 8. Health and Beauty --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/health-and-beauty.jpg') }}"
                     alt="Health and Beauty">
            </div>
            <span>Health &amp; Beauty</span>
        </a>

        {{-- 9. Books and Media --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/books-and-media.jpg') }}"
                     alt="Books and Media">
            </div>
            <span>Books &amp; Media</span>
        </a>

        {{-- 10. Food and Gourmet --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/food-and-gourmet.jpg') }}"
                     alt="Food and Gourmet">
            </div>
            <span>Food &amp; Gourmet</span>
        </a>

        {{-- 11. Automotive & Motorcycle --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/automotive-and-motorcycle.jpg') }}"
                     alt="Automotive and Motorcycle">
            </div>
            <span>Automotive &amp; Motorcycle</span>
        </a>

        {{-- 12. Furniture and Office Equipment --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/furniture-and-office.jpg') }}"
                     alt="Furniture and Office Equipment">
            </div>
            <span>Furniture &amp; Office</span>
        </a>

        {{-- 13. Jewelry and Watches --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/jewelry-and-watches.jpg') }}"
                     alt="Jewelry and Watches">
            </div>
            <span>Jewelry &amp; Watches</span>
        </a>

        {{-- 14. Office and School Supplies --}}
        <a href="{{ route('buyer.login') }}" class="cat-card reveal-scale">
            <div class="cat-photo">
                <img src="{{ asset('images/categories/office-and-school.jpg') }}"
                     alt="Office and School Supplies">
            </div>
            <span>Office &amp; School</span>
        </a>

    </div>
    </div>

</div>

</section>
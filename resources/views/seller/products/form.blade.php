@php
    $selectedSizes = array_filter(array_map('trim', explode(',', $product->sizes ?? '')));
    $selectedColors = array_filter(array_map('trim', explode(',', $product->colors ?? '')));
    $existingImages = $product->exists ? $product->images : collect();
@endphp
<form method="POST" action="{{ $product->exists ? route('seller.products.update', $product) : route('seller.products.store') }}" enctype="multipart/form-data" data-operation data-product-form class="ops-form">
    @csrf
    @if($product->exists)
        @method('PATCH')
        <input type="hidden" name="expected_revision" value="{{ $product->revision }}">
    @endif
    <p class="ops-muted">{{ $product->exists ? 'Changes to listing details and photos are sent to admin for review.' : 'New products become visible to buyers after admin approval.' }}</p>
    @if($categories->isEmpty())<p class="ops-alert">No selling categories are assigned to your account. Contact the administrator before adding a product.</p>@endif

    <label>Product name<input name="name" required maxlength="255" value="{{ $product->name }}"></label>
    <label>Description<textarea name="description" maxlength="5000" rows="4">{{ $product->description }}</textarea></label>
    <div class="ops-form-grid">
        <label>Category<select name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label>Price (₱)<input name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $product->price }}" required></label>
    </div>
    @unless($product->exists)
        <label>Opening stock<input name="stock" type="number" min="0" max="1000000" step="1" required value="0"></label>
    @else
        <p class="ops-caption">Stock: {{ $product->stock }} units. Use Restock to record new stock separately.</p>
    @endunless

    <div class="ops-form-grid">
        <label>Brand<input name="brand" maxlength="255" value="{{ $product->brand }}"></label>
        <label>Material<input name="material" required maxlength="255" value="{{ $product->material }}" placeholder="e.g. Cotton, stainless steel"></label>
        <label>Weight<input name="weight" required maxlength="30" value="{{ $product->weight }}" placeholder="e.g. 500 g or 1.25 kg" pattern="[0-9]+(\.[0-9]{1,2})?\s*(g|kg|G|KG|Kg)" title="Enter a positive weight such as 500 g or 1.25 kg"></label>
        <label>Country of origin<input value="Philippines" readonly aria-readonly="true"><small>Vendo currently accepts products originating in the Philippines.</small></label>
    </div>

    <details class="ops-choice">
        <summary>Available sizes <span class="ops-choice-count" data-size-count>{{ count($selectedSizes) ? count($selectedSizes).' selected' : 'Optional' }}</span></summary>
        <div class="ops-size-options">
            @foreach(['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size)
                <label><input type="checkbox" name="sizes[]" value="{{ $size }}" @checked(in_array($size, $selectedSizes, true))>{{ $size }}</label>
            @endforeach
        </div>
    </details>

    <div class="ops-field" data-color-picker>
        <span class="ops-field-label">Available colors <small>Optional; choose more than one if needed.</small></span>
        <div class="ops-color-chips" data-color-selected>
            @foreach($selectedColors as $color)
                <span class="ops-color-chip"><span>{{ $color }}</span><input type="hidden" name="colors[]" value="{{ $color }}"><button type="button" data-color-remove aria-label="Remove {{ $color }}">×</button></span>
            @endforeach
        </div>
        <button type="button" class="ops-button ops-button--small" data-color-open>＋ Add Color</button>
        <div class="ops-color-picker" data-color-popover hidden>
            <label>Search colors<input type="search" data-color-search placeholder="Search or type a color" autocomplete="off" maxlength="30"></label>
            <div class="ops-color-results" data-color-results role="listbox" aria-label="Matching colors"></div>
            <button type="button" class="ops-text-link" data-color-custom hidden>Add this color</button>
            <small>Up to 10 colors. Select a result, then search again to add another.</small>
        </div>
    </div>

    <section class="ops-photo-field" data-photo-field>
        <h3>Product photos</h3>
        <p class="ops-caption">One main photo and up to five additional photos. JPG, PNG or WebP; 2 MB each, 7 MB total per save.</p>
        @if($existingImages->isNotEmpty())
            <div class="ops-photo-grid">
                @foreach($existingImages as $image)
                    <label class="ops-photo-existing"><img src="{{ asset('storage/'.$image->path) }}" alt="Photo {{ $loop->iteration }} of {{ $product->name }}"><span>{{ $loop->first ? 'Main photo' : 'Gallery photo '.$loop->iteration }}</span><span><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" data-remove-photo> Remove</span></label>
                @endforeach
            </div>
        @endif
        <label>Main photo<input name="main_image" type="file" accept="image/jpeg,image/png,image/webp" @if($existingImages->isEmpty()) required @endif data-main-photo><small>{{ $existingImages->isNotEmpty() ? 'Choose a file to replace the current main photo.' : 'Choose the photo buyers see first.' }}</small></label>
        <label>Additional photos<input name="gallery_images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple data-gallery-photos><small data-gallery-slots>{{ max(0, 6 - $existingImages->count()) }} gallery slots available. You may remove existing photos above to free slots.</small></label>
        <p class="ops-alert" data-photo-error role="alert" hidden></p>
    </section>

    <p class="ops-alert" data-form-error role="alert" hidden></p>
    <button class="ops-button ops-button--primary" @disabled($categories->isEmpty())>{{ $product->exists ? 'Save for Review' : 'Submit Product' }}</button>
</form>

<form method="POST" action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}" enctype="multipart/form-data" data-operation class="ops-form">
    @csrf
    @if($product->exists)@method('PATCH')<input type="hidden" name="expected_revision" value="{{ $product->revision }}">@endif
    <p class="ops-muted">{{ $product->exists ? 'Changes to listing details are sent to admin for review.' : 'New products become visible to buyers after admin approval.' }}</p>
    @if($categories->isEmpty())<p class="ops-alert">No selling categories are assigned to your account. Contact the administrator before adding a product.</p>@endif
    <label>Product name<input name="name" required maxlength="255" value="{{ $product->name }}"></label>
    <label>Description<textarea name="description" maxlength="5000" rows="4">{{ $product->description }}</textarea></label>
    <div class="ops-form-grid"><label>Category<select name="category_id" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($product->category_id===$category->id)>{{ $category->name }}</option>@endforeach</select></label><label>Price (₱)<input name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ $product->price }}" required></label></div>
    @unless($product->exists)<label>Opening stock<input name="stock" type="number" min="0" max="1000000" step="1" required value="0"></label>@else<p class="ops-caption">Stock: {{ $product->stock }} units. Use Restock to record new stock separately.</p>@endunless
    <div class="ops-form-grid"><label>Brand<input name="brand" maxlength="255" value="{{ $product->brand }}"></label><label>Colors<input name="colors" maxlength="255" value="{{ $product->colors }}"></label><label>Sizes<input name="sizes" maxlength="255" value="{{ $product->sizes }}"></label></div>
    <label>Product image<input name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP, up to 2 MB. A new image becomes the main photo.</small></label>
    <p class="ops-alert" data-form-error role="alert" hidden></p>
    <button class="ops-button ops-button--primary" @disabled($categories->isEmpty())>{{ $product->exists?'Save for Review':'Submit Product' }}</button>
</form>

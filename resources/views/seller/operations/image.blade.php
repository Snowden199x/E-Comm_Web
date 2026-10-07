@if($image=$product?->images->first())
    <img class="ops-thumb" src="{{ asset('storage/'.$image->path) }}" alt="{{ $product->name }}" loading="lazy">
@else
    <span class="ops-thumb ops-thumb--empty" aria-label="No product image"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8zM3 8l9 5 9-5M12 13v8M7 5.8l9 5"/></svg></span>
@endif

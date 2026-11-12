<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\BuyerAddress;
use App\Services\PsgcAddressResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Buyer-saved delivery addresses. Every lookup is scoped to the signed-in Buyer,
 * and localities are canonicalized against the bundled PSGC catalog on the server.
 */
class AddressBookController extends Controller
{
    public const MAX_ADDRESSES = 10;

    private const MESSAGES = [
        'label.required' => 'Give this address a name, like Home or Office.',
        'label.max' => 'Use 40 characters or fewer for the name.',
        'street.required' => 'Enter the street so the rider can find you.',
        'street.max' => 'Use 180 characters or fewer for the street.',
        'barangay.required' => 'Enter your barangay.',
        'municipality.required' => 'Enter your city or municipality.',
        'province.required' => 'Enter your province.',
        'house_no.max' => 'Use 60 characters or fewer for the house number.',
        'zip_code.max' => 'Use 12 characters or fewer for the ZIP code.',
        'latitude.numeric' => 'Move the pin on the map, then save again.',
        'latitude.between' => 'Move the pin onto the map, then save again.',
        'longitude.numeric' => 'Move the pin on the map, then save again.',
        'longitude.between' => 'Move the pin onto the map, then save again.',
        'longitude.required_with' => 'Move the pin on the map, then save again.',
    ];

    public function index(Request $request): View
    {
        $this->ensureBuyer($request);
        $addresses = $this->addressesFor($request)->orderByDesc('is_default')->oldest('id')->get();

        return view('buyer.addresses.index', [
            'addresses' => $addresses,
            'maxAddresses' => self::MAX_ADDRESSES,
        ]);
    }

    public function store(Request $request, PsgcAddressResolver $resolver): RedirectResponse
    {
        $this->ensureBuyer($request);

        if ($this->addressesFor($request)->count() >= self::MAX_ADDRESSES) {
            throw ValidationException::withMessages([
                'label' => 'You can save up to '.self::MAX_ADDRESSES.' addresses. Delete one to add another.',
            ]);
        }

        $data = $this->validated($request, $resolver);

        DB::transaction(function () use ($request, $data) {
            $hasDefault = $this->addressesFor($request)->where('is_default', true)->exists();

            BuyerAddress::create([
                ...$data,
                'user_id' => $request->user()->id,
                'is_default' => ! $hasDefault,
            ]);
        }, 3);

        return redirect()->route('buyer.addresses.index')->with('success', 'Address saved.');
    }

    public function update(Request $request, BuyerAddress $address, PsgcAddressResolver $resolver): RedirectResponse
    {
        $this->ensureOwner($request, $address);
        $data = $this->validated($request, $resolver);

        // Saving without reopening the map must not erase a pin already placed.
        $data['latitude'] = $request->filled('latitude') ? $data['latitude'] : $address->latitude;
        $data['longitude'] = $request->filled('latitude') ? $data['longitude'] : $address->longitude;

        $address->update($data);

        return redirect()->route('buyer.addresses.index')->with('success', 'Address updated.');
    }

    public function destroy(Request $request, BuyerAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $this->addressesFor($request)->oldest('id')->first();
                $next?->update(['is_default' => true]);
            }
        }, 3);

        return redirect()->route('buyer.addresses.index')->with('success', 'Address deleted.');
    }

    public function makeDefault(Request $request, BuyerAddress $address): RedirectResponse
    {
        $this->ensureOwner($request, $address);

        DB::transaction(function () use ($request, $address) {
            $this->addressesFor($request)->where('is_default', true)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        }, 3);

        return redirect()->route('buyer.addresses.index')->with('success', 'Default address changed.');
    }

    private function addressesFor(Request $request)
    {
        return BuyerAddress::query()->where('user_id', $request->user()->id);
    }

    private function ensureBuyer(Request $request): void
    {
        abort_unless($request->user()?->role === 'buyer', 403);
    }

    private function ensureOwner(Request $request, BuyerAddress $address): void
    {
        $this->ensureBuyer($request);
        abort_unless($address->user_id === $request->user()->id, 404);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, PsgcAddressResolver $resolver): array
    {
        $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'house_no' => ['nullable', 'string', 'max:60'],
            'street' => ['required', 'string', 'max:180'],
            'barangay' => ['required', 'string', 'max:120'],
            'municipality' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'zip_code' => ['nullable', 'string', 'max:12'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ], self::MESSAGES);

        return [
            ...$resolver->resolve($request->only('province', 'municipality', 'barangay')),
            'label' => $request->string('label')->trim()->toString(),
            'house_no' => $request->filled('house_no') ? $request->string('house_no')->trim()->toString() : null,
            'street' => $request->string('street')->trim()->toString(),
            'zip_code' => $request->filled('zip_code') ? $request->string('zip_code')->trim()->toString() : null,
            'latitude' => $request->filled('latitude') ? $request->float('latitude') : null,
            'longitude' => $request->filled('longitude') ? $request->float('longitude') : null,
        ];
    }
}

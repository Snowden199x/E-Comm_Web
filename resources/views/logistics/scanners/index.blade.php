<x-logistics.layout title="SH Scanners">
    <div class="lg-page">
        <div class="lg-page-head">
            <div>
                <h1>SH Scanners</h1>
                <p>Pair a device to one active route checkpoint. A scan records an operator-confirmed SH arrival and sorting event.</p>
            </div>
        </div>

        @if(session('pairing'))
            <section class="lg-card mb-6 p-6" aria-labelledby="pairing-title">
                <h2 id="pairing-title" class="text-lg font-semibold">Pairing QR for station #{{ session('pairing')['station_id'] }}</h2>
                <p class="mt-1 text-sm">Scan this QR in Vendo Scanner within 5 minutes. It works once. Do not share or print it.</p>
                <img src="{{ session('pairing')['qr'] }}" alt="Single-use scanner pairing QR" class="mt-4 h-52 w-52 bg-white p-2">
                <p class="mt-3 break-all text-xs">Manual pairing code: <code>{{ session('pairing')['code'] }}</code></p>
            </section>
        @endif

        <section class="lg-card mb-6 p-6" aria-labelledby="new-station-title">
            <h2 id="new-station-title" class="text-lg font-semibold">Pair a new station</h2>
            @if($checkpoints->isEmpty())
                <p class="mt-2 text-sm">No active SH checkpoints are available on an active route connected to this Main Hub. Configure the route and checkpoint first.</p>
            @else
                <form method="POST" action="{{ route('logistics.scanners.store') }}" class="mt-4 flex flex-wrap items-end gap-3">@csrf
                    <label class="text-sm">Station name
                        <input class="lg-input mt-1 block" name="name" required maxlength="100" value="{{ old('name') }}" placeholder="e.g. SH6 scanning phone">
                    </label>
                    <label class="text-sm">Checkpoint
                        <select class="lg-input mt-1 block" name="checkpoint_id" required>
                            <option value="">Choose SH</option>
                            @foreach($checkpoints as $checkpoint)
                                <option value="{{ $checkpoint->id }}" @selected(old('checkpoint_id') == $checkpoint->id)>{{ $checkpoint->code }} — {{ $checkpoint->name }}, {{ $checkpoint->province }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="lg-btn" type="submit">Generate pairing QR</button>
                </form>
            @endif
        </section>

        <section class="lg-card" aria-labelledby="stations-title">
            <h2 id="stations-title" class="p-6 text-lg font-semibold">Stations</h2>
            @forelse($stations as $station)
                <div class="lg-row">
                    <div class="lg-row__main">
                        <div class="lg-person"><span><strong>{{ $station->name }}</strong><small>{{ $station->checkpoint?->code }} — {{ $station->checkpoint?->name }}</small></span></div>
                        <div class="lg-row__meta">{{ $station->revoked_at ? 'Revoked' : ($station->paired_at ? 'Paired' : 'Waiting for pairing') }}<small>{{ $station->paired_at?->format('M j, Y g:i A') }}</small></div>
                        @unless($station->revoked_at)
                            @unless($station->paired_at)
                                <form method="POST" action="{{ route('logistics.scanners.pairing', $station) }}">@csrf
                                    <button type="submit" class="lg-btn lg-btn--outline lg-btn--sm">New pairing QR</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('logistics.scanners.revoke', $station) }}">@csrf @method('DELETE')
                                <button type="submit" class="lg-btn lg-btn--outline lg-btn--sm">Revoke</button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="lg-empty"><h2>No scanner stations yet</h2><p>Generate a pairing QR to add the first device.</p></div>
            @endforelse
        </section>
    </div>
</x-logistics.layout>

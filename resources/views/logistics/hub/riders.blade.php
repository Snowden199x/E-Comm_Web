@php
    $tabs = ['active' => 'Active', 'applications' => 'Applications', 'rejected' => 'Rejected', 'inactive' => 'Inactive'];
    $riders = [
        ['Jun Bautista', 'jun@mail.com', 'Motorcycle', 'Santa Cruz', 14, 'active'],
        ['Paolo Garcia', 'paolo@mail.com', 'Motorcycle', 'Pagsanjan', 12, 'active'],
        ['Nina Castillo', 'nina@mail.com', 'Tricycle', 'Santa Cruz', 9, 'active'],
        ['Kris Mendoza', 'kris@mail.com', 'Motorcycle', 'Santa Cruz', 0, 'applications'],
        ['Dante Ruiz', 'dante@mail.com', 'Bicycle', 'Santa Cruz', 0, 'applications'],
        ['Leo Tan', 'leo@mail.com', 'Motorcycle', 'Liliw', 0, 'applications'],
        ['Mia Cruz', 'mia@mail.com', 'Motorcycle', 'Santa Cruz', 0, 'rejected'],
        ['Rey Santos', 'rey@mail.com', 'Tricycle', 'Santa Cruz', 0, 'inactive'],
    ];
@endphp
<x-logistics.shell title="Rider Management" :role="$role">
    <div class="lg-page" x-data="{ tab: 'active' }">
        <div class="lg-page-head"><div><h1>Rider Management</h1><p>Review applicants and manage riders linked to this hub.</p></div></div>
        <nav class="lg-tabs" aria-label="Rider status">
            @foreach ($tabs as $k => $l)
                <button type="button" class="lg-tab" :aria-current="tab === '{{ $k }}' ? 'page' : null" @click="tab = '{{ $k }}'">{{ $l }} <span class="lg-tab__count">{{ collect($riders)->where(5, $k)->count() }}</span></button>
            @endforeach
        </nav>
        @foreach ($tabs as $k => $l)
        <section class="lg-card" x-show="tab === '{{ $k }}'" @if ($k !== 'active') x-cloak @endif aria-label="{{ $l }}">
            <div class="lg-table-wrap"><table class="lg-table">
                <thead><tr><th>Rider</th><th>Vehicle</th><th>Area</th><th class="is-num">Active parcels</th><th></th></tr></thead>
                <tbody>
                @foreach (collect($riders)->where(5, $k) as [$n, $e, $v, $a, $p])
                    <tr>
                        <td><div class="lg-person"><span class="lg-avatar lg-avatar--sm" aria-hidden="true">{{ $n[0] }}</span><span><strong>{{ $n }}</strong><small>{{ $e }}</small></span></div></td>
                        <td>{{ $v }}</td><td>{{ $a }}</td><td class="is-num">{{ $p }}</td>
                        <td>
                            @if ($k === 'applications')
                                <span class="lg-doc-links"><a class="lg-doc-link" href="#">Valid ID</a><a class="lg-doc-link" href="#">License</a><a class="lg-doc-link" href="#">OR / CR</a></span>
                                <button type="button" class="lg-btn lg-btn--sm">Approve</button>
                                <button type="button" class="lg-btn lg-btn--danger lg-btn--sm">Reject</button>
                            @elseif ($k === 'active')
                                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm">Deactivate</button>
                            @elseif ($k === 'inactive')
                                <button type="button" class="lg-btn lg-btn--outline lg-btn--sm">Activate</button>
                            @else
                                <span class="lg-pill lg-pill--red">Rejected</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </section>
        @endforeach
    </div>
</x-logistics.shell>
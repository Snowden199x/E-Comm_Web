<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Vendo Logistics Report</title>
<style>body{font-family:DejaVu Sans,sans-serif;color:#2b1730;font-size:11px}h1{font-size:18px}h2{font-size:13px;margin-top:24px}p{color:#555}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:6px;text-align:left}th:last-child,td:last-child{text-align:right}</style>
</head><body>
<h1>Vendo Logistics Report</h1>
<p>{{ $since->format('M j, Y') }} to {{ $until->format('M j, Y') }} · Main Hub account #{{ auth()->id() }}</p>
<p>{{ number_format($total) }} updated parcels · {{ number_format($delivered) }} delivered · {{ number_format($exceptions) }} exceptions</p>
<h2>Current status</h2><table><tr><th>Status</th><th>Parcels</th></tr>
@foreach($statusCounts as $row)<tr><td>{{ ucwords(str_replace('_', ' ', $row->status)) }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
<h2>Delivery area</h2><table><tr><th>Area</th><th>Parcels</th></tr>
@foreach($areaCounts as $row)<tr><td>{{ $row->shipping_city ?: 'Unknown' }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
<h2>Delivery rider</h2><table><tr><th>Rider</th><th>Parcels</th></tr>
@foreach($riderCounts as $row)<tr><td>{{ $row->deliveryCourier?->name ?: 'Unavailable' }}</td><td>{{ $row->total }}</td></tr>@endforeach</table>
</body></html>

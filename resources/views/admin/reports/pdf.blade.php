<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ public_path('assets/css/admin/report-pdf.css') }}">
</head>

<body>
    <div class="header">
        <img src="{{ public_path('assets/branding/vendo-logo@2x.png') }}">
        <div>
            <div class="title">Vendo {{ $view === 'monthly' ? 'Yearly' : 'Monthly' }} Report</div>
            <div class="subtitle">{{ $periodLabel }}</div>
        </div>
    </div>

    <h3>Sales Summary</h3>
    <table class="grid">
        <tr>
            <td>
                <div class="card">
                    <div class="label">Gross Sales</div>
                    <div class="value">&#8369;{{ number_format($salesSummary['gross_sales'], 2) }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Total Orders</div>
                    <div class="value">{{ number_format($salesSummary['total_orders']) }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Average Order Value</div>
                    <div class="value">&#8369;{{ number_format($salesSummary['average_order_value'], 2) }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="card">
                    <div class="label">Completed Orders</div>
                    <div class="value">{{ number_format($salesSummary['completed_orders']) }}</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="label">Return/Refund</div>
                    <div class="value">{{ number_format($salesSummary['return_refund']) }}</div>
                </div>
            </td>
            <td></td>
        </tr>
    </table>

    <h3>{{ $breakdownTitle }}</h3>
    <table class="data">
        <thead>
            <tr>
                <th>{{ $view === 'monthly' ? 'Month' : 'Day' }}</th>
                <th class="text-right">Sales</th>
                <th class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($chartLabels as $i => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="text-right">&#8369;{{ number_format($chartSales[$i], 2) }}</td>
                    <td class="text-right green">&#8369;{{ number_format($chartCommission[$i], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#9ca3af;">No data recorded.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3>Commission Report by Seller ({{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%)</h3>
    <table class="data">
        <thead>
            <tr>
                <th>Seller</th>
                <th class="text-right">Sales</th>
                <th class="text-right">Commission</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sellers as $seller)
                <tr>
                    <td>{{ $seller['name'] }}</td>
                    <td class="text-right">&#8369;{{ number_format($seller['sales'], 2) }}</td>
                    <td class="text-right green">&#8369;{{ number_format($seller['commission'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#9ca3af;">No sales recorded.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($sellers->isNotEmpty())
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td class="text-right green">&#8369;{{ number_format($totalCommission, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>

</html>

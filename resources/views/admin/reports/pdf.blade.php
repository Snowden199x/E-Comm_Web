<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 12px;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #3b1735;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .header img {
            height: 40px;
            margin-right: 12px;
        }

        .header .title {
            font-size: 16px;
            font-weight: bold;
            color: #3b1735;
        }

        .header .subtitle {
            font-size: 11px;
            color: #6b7280;
        }

        h3 {
            color: #3b1735;
            font-size: 13px;
            margin-top: 24px;
            margin-bottom: 10px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .grid td {
            width: 33%;
            padding: 8px;
        }

        .card {
            background: #f9fafb;
            border-radius: 6px;
            padding: 10px;
        }

        .card .label {
            font-size: 10px;
            color: #6b7280;
        }

        .card .value {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.data th {
            text-align: left;
            border-bottom: 1px solid #d1d5db;
            padding: 6px 4px;
            color: #6b7280;
        }

        table.data td {
            padding: 6px 4px;
            border-bottom: 1px solid #f3f4f6;
        }

        table.data tfoot td {
            border-top: 2px solid #d1d5db;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .green {
            color: #15803d;
        }
    </style>
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

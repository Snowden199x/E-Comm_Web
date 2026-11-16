<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px 34px; }
        body { color: #302335; font: 8px DejaVu Sans, sans-serif; }
        h1 { color: #43234c; font-size: 18px; margin: 0 0 5px; text-align: center; }
        .intro { color: #695f6d; font-size: 9px; margin-bottom: 12px; text-align: center; }
        .warning { background: #f8edf1; border: 1px solid #e8c8d2; margin: 10px 0 14px; padding: 8px; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        th { background: #4b2452; color: white; text-align: left; }
        th, td { border: 1px solid #ddd5e1; padding: 4px 5px; vertical-align: top; }
        tr:nth-child(even) td { background: #f6f2f7; }
        .email { font-size: 7px; word-wrap: break-word; }
        .password { white-space: nowrap; }
        .company-start td { border-top: 2px solid #806487; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        footer { bottom: -20px; color: #746b76; font-size: 8px; left: 0; position: fixed; right: 0; }
        footer .page { bottom: 0; position: absolute; right: 0; }
        .page:after { content: "Page " counter(page); }
    </style>
</head>
<body>
    <h1>{{ $companySection ?? 'Vendo Logistics' }} - Nationwide Demo Accounts</h1>
    <div class="intro">Company and facility-manager logins for the full PSGC province and municipality network.</div>
    <p><strong>Shared demo password:</strong> password123 &nbsp; <strong>Sign in:</strong> Logistics portal &nbsp; <strong>Email domain:</strong> @vendo.com</p>
    <div class="warning"><strong>School presentation demo credentials.</strong> Every Company, Province Hub, and Station Hub manager account uses the shared password shown above. Keep this file restricted to the project team.</div>
    <table>
        <colgroup><col style="width: 15%"><col style="width: 14%"><col style="width: 28%"><col style="width: 16%"><col style="width: 20%"><col style="width: 7%"></colgroup>
        <thead><tr><th>Company</th><th>Province / Area</th><th>Facility</th><th>Account role</th><th>Email</th><th>Password</th></tr></thead>
        <tbody>
        @foreach ($accounts as $index => $account)
            <tr @if ($index > 0 && $accounts[$index - 1]['company'] !== $account['company']) class="company-start" @endif>
                <td>{{ $account['company'] }}</td>
                <td>{{ $account['province'] }}</td>
                <td>{{ $account['facility'] }}</td>
                <td>{{ $account['account_type'] }}</td>
                <td class="email">{{ $account['email'] }}</td>
                <td class="password">{{ $account['password'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <footer><span>Confidential local demo credentials</span><span class="page"></span></footer>
</body>
</html>

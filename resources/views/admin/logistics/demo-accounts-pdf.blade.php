<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px 34px; }
        body { color: #302335; font: 9px DejaVu Sans, sans-serif; }
        h1 { color: #43234c; font-size: 18px; margin: 0 0 5px; text-align: center; }
        .intro { color: #695f6d; font-size: 9px; margin-bottom: 12px; text-align: center; }
        .warning { background: #f8edf1; border: 1px solid #e8c8d2; margin: 10px 0 14px; padding: 8px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #4b2452; color: white; text-align: left; }
        th, td { border: 1px solid #ddd5e1; padding: 5px 7px; }
        tr:nth-child(even) td { background: #f6f2f7; }
        footer { bottom: -20px; color: #746b76; font-size: 8px; left: 0; position: fixed; right: 0; }
        .page:after { content: "Page " counter(page); float: right; }
    </style>
</head>
<body>
    <h1>Vendo Logistics - Local Demo Accounts</h1>
    <div class="intro">Company, Province Hub Manager, and Station Hub Manager accounts for the PSGC province/municipality network.</div>
    <p><strong>Shared demo password:</strong> password123 &nbsp; <strong>Sign in:</strong> Logistics portal &nbsp; <strong>Email domain:</strong> @vendo.com</p>
    <div class="warning"><strong>School presentation demo accounts.</strong> All accounts use the same demo password.</div>
    <table>
        <thead><tr><th>Province / Area</th><th>Account type / facility</th><th>Email</th><th>Password</th></tr></thead>
        <tbody>
        @foreach ($accounts as $account)
            <tr><td>{{ $account['province'] }}</td><td>{{ $account['account_type'] }}</td><td>{{ $account['email'] }}</td><td>{{ $account['password'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <footer>Confidential local demo credentials <span class="page"></span></footer>
</body>
</html>

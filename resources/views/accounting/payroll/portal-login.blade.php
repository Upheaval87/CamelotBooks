<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Payslip Portal — Login</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',system-ui,sans-serif; background:#eef4f4; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .pd-portal-login { display:grid; grid-template-columns:1fr 1fr; max-width:960px; width:100%; min-height:600px; border-radius:24px; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,.08); }
        .pd-portal-brand { background:linear-gradient(135deg,#17565d 0%,#128F8E 50%,#0a2e32 100%); display:flex; flex-direction:column; align-items:center; justify-content:center; padding:48px; color:#fff; text-align:center; }
        .pd-portal-brand h1 { font-size:32px; font-weight:800; letter-spacing:-0.5px; margin-bottom:12px; }
        .pd-portal-brand p { font-size:14px; opacity:.8; max-width:300px; line-height:1.6; }
        .pd-portal-form-col { background:#fff; padding:48px; display:flex; flex-direction:column; justify-content:center; }
        .pd-portal-form-col h2 { font-size:22px; font-weight:800; color:#111827; margin-bottom:6px; }
        .pd-portal-form-col .sub { font-size:13px; color:#5f7476; margin-bottom:28px; }
        .pd-portal-field { margin-bottom:18px; }
        .pd-portal-field label { display:block; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748b; margin-bottom:6px; }
        .pd-portal-field input { width:100%; height:44px; border:1.5px solid #dceaea; border-radius:12px; padding:0 14px; font-size:14px; font-family:inherit; outline:none; transition:border-color .15s; }
        .pd-portal-field input:focus { border-color:#128F8E; box-shadow:0 0 0 3px rgba(18,143,142,.12); }
        .pd-portal-submit { width:100%; height:48px; border:none; border-radius:12px; background:linear-gradient(180deg,#17565d,#128F8E 55%,#0a2e32); color:#fff; font-size:15px; font-weight:700; font-family:inherit; cursor:pointer; margin-top:8px; transition:transform .1s; }
        .pd-portal-submit:hover { transform:translateY(-1px); }
        .pd-portal-error { background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; color:#b91c1c; font-size:13px; margin-bottom:18px; }
        @media(max-width:720px) { .pd-portal-login { grid-template-columns:1fr; } .pd-portal-brand { padding:32px; min-height:180px; } }
    </style>
</head>
<body>
    <div class="pd-portal-login">
        <div class="pd-portal-brand">
            <h1>CB</h1>
            <p>Employee Payslip Portal — view and download your payslips securely.</p>
        </div>
        <div class="pd-portal-form-col">
            <h2>Sign In</h2>
            <div class="sub">Enter your employee number and payslip password.</div>

            @if($errors->any())
                <div class="pd-portal-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('accounting.payroll.portal.authenticate') }}">
                @csrf
                <div class="pd-portal-field">
                    <label for="employee_number">Employee Number</label>
                    <input type="text" id="employee_number" name="employee_number" value="{{ old('employee_number') }}" required autofocus>
                </div>
                <div class="pd-portal-field">
                    <label for="password">Payslip Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="pd-portal-submit">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email</title>
</head>
<body style="font-family: 'Poppins', Arial, sans-serif; background:#f5f0eb; margin:0; padding:40px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" width="420" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background:#3b1735; padding:24px; text-align:center;">
                            <span style="color:#ffffff; font-size:20px; font-weight:700;">Vendo</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px; text-align:center;">
                            <h2 style="color:#111827; font-size:18px; margin:0 0 8px;">Verify Your Email</h2>
                            <p style="color:#6b7280; font-size:13px; margin:0 0 24px;">
                                Use the code below to verify your email address. It expires in 10 minutes.
                            </p>
                            <div style="font-size:32px; font-weight:700; letter-spacing:10px; color:#3b1735; margin:0 0 24px;">
                                {{ $code }}
                            </div>
                            <p style="color:#9ca3af; font-size:12px; margin:0;">
                                If you didn't request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

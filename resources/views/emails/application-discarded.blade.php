@php
    $logoUrl = asset('THEVELVETSTUDIO_BRANDBOOK-55.png');
    $discardedLabel = $discardedAt?->format('d/m/Y');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $subject }} · The Velvet Studio</title>
    <style>
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; }
        @media only screen and (max-width: 680px) {
            .email-shell { width: 100% !important; }
            .email-gutter { padding-left: 22px !important; padding-right: 22px !important; }
            .email-title { font-size: 37px !important; line-height: 1.04 !important; }
            .email-card { padding: 24px 20px !important; }
            .email-quote { padding: 22px 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#100b18; color:#f7f1fb; font-family:Arial,Helvetica,sans-serif;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">Gracias por compartir tu historia con The Velvet Studio. Próximamente abriremos nuevas oportunidades.</span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#100b18;">
        <tr>
            <td align="center" class="email-gutter" style="padding:22px 14px 34px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:100%; max-width:660px; background:#080a10; border:1px solid #292936; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="height:4px; background:linear-gradient(90deg,#70218b,#d74bf1,#70218b); font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:28px 36px 20px;">
                            <img src="{{ $logoUrl }}" width="190" alt="The Velvet Studio" style="display:block; width:190px; max-width:100%; height:auto; border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:18px 30px 0;">
                            <p style="margin:0 0 17px; color:#d96eea; font-size:11px; line-height:18px; letter-spacing:4px; text-transform:uppercase;">{{ $stageLabel }}</p>
                            <h1 class="email-title" style="margin:0; color:#fbf8fc; font-family:Georgia,'Times New Roman',serif; font-size:48px; font-weight:400; line-height:1.02; letter-spacing:-1.8px;">{{ $headline }}</h1>
                            <p style="margin:22px 0 0; color:#c4beca; font-size:16px; line-height:26px;">Hola {{ $applicantName }},</p>
                            <p style="margin:8px 0 0; color:#b7b1bf; font-size:15px; line-height:25px;">{{ $discardMessage }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:28px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:linear-gradient(135deg,#24132f,#120d19); border:1px solid #713080; border-radius:10px;">
                                <tr>
                                    <td class="email-card" style="padding:28px 26px;">
                                        <p style="margin:0 0 10px; color:#d96eea; font-size:11px; line-height:18px; letter-spacing:3px; text-transform:uppercase;">Una nota para ti</p>
                                        <p style="margin:0; color:#f7f1fb; font-family:Georgia,'Times New Roman',serif; font-size:25px; line-height:34px;">{{ $encouragement }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:30px 30px 0;">
                            <h2 style="margin:0; color:#fbf8fc; font-family:Georgia,'Times New Roman',serif; font-size:30px; font-weight:400; line-height:38px;">¿Qué sigue?</h2>
                            <p style="margin:10px 0 0; color:#b7b1bf; font-size:15px; line-height:25px;">{{ $nextSteps }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:28px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #34313d; border-radius:9px; background:#0e1118;">
                                <tr>
                                    <td class="email-quote" align="center" style="padding:24px 26px;">
                                        <div style="margin:0 auto 12px; width:42px; height:42px; border:1px solid #c23bea; border-radius:50%; color:#e0a0ed; font-size:23px; line-height:42px;">✦</div>
                                        <p style="margin:0; color:#f7f1fb; font-size:15px; font-weight:bold; line-height:22px;">Aquí comienzan grandes historias.</p>
                                        <p style="margin:5px 0 0; color:#aaa5b2; font-size:13px; line-height:21px;">Gracias por confiar en The Velvet Studio.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:30px 30px 10px; color:#a9a3b1; font-size:12px; letter-spacing:4px;">REAL PEOPLE. BIGGER STORIES.</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 30px 30px; color:#777380; font-size:12px; line-height:20px;">The Velvet Studio · {{ $stageLabel }} · {{ $discardedLabel }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

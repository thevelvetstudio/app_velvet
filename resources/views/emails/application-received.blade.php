@php
    $logoUrl = asset('THEVELVETSTUDIO_BRANDBOOK-55.png');
    $heroUrl = asset('onboarding/modelo.webp');
    $typeLabel = $candidateType === 'MONITOR' ? 'Monitor(a)' : 'Modelo Webcam';
    $submittedLabel = $submittedAt?->format('d/m/Y');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Recibimos tu aplicación · The Velvet Studio</title>
    <style>
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; }
        @media only screen and (max-width: 680px) {
            .email-shell { width: 100% !important; }
            .email-gutter { padding-left: 22px !important; padding-right: 22px !important; }
            .email-hero-copy, .email-hero-image, .email-id-cell, .email-code-cell { display: block !important; width: 100% !important; }
            .email-hero-copy { padding: 32px 22px !important; }
            .email-hero-copy h1 { font-size: 35px !important; line-height: 1.06 !important; letter-spacing: -1px !important; }
            .email-hero-image img { height: 250px !important; width: 100% !important; }
            .email-id-cell { padding: 24px 10px 8px !important; }
            .email-code-cell { padding: 8px 20px 24px !important; }
            .email-step { display: block !important; width: 100% !important; padding: 18px 0 !important; border-right: 0 !important; border-bottom: 1px solid #292936; }
            .email-step:last-child { border-bottom: 0 !important; }
            .email-code { font-size: 24px !important; line-height: 31px !important; letter-spacing: 2px !important; word-break: break-word !important; }
            .email-title { font-size: 27px !important; line-height: 34px !important; }
            .email-footer { padding-left: 22px !important; padding-right: 22px !important; }
        }
        @media only screen and (max-width: 380px) {
            .email-code { font-size: 20px !important; letter-spacing: 1px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#100b18; color:#f7f1fb; font-family:Arial,Helvetica,sans-serif;">
    <span style="display:none!important; visibility:hidden; opacity:0; color:transparent; height:0; width:0; overflow:hidden;">Tu aplicación está siendo revisada. Guarda tu código de aplicación para futuras comunicaciones.</span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#100b18;">
        <tr>
            <td align="center" class="email-gutter" style="padding:22px 14px 34px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-shell" style="width:100%; max-width:660px; background:#080a10; border:1px solid #292936; border-radius:14px; overflow:hidden;">
                    <tr>
                        <td style="padding:22px 36px 14px; color:#a8a5b0; font-size:13px; line-height:20px;">Tu talento, nuevas oportunidades.</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:8px 36px 30px;">
                            <img src="{{ $logoUrl }}" width="190" alt="The Velvet Studio" style="display:block; width:190px; max-width:100%; height:auto; border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:0 20px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#1b0e25; border-radius:8px; overflow:hidden;">
                                <tr>
                                    <td class="email-hero-copy" width="54%" valign="middle" style="width:54%; padding:40px 28px 38px 34px; background:linear-gradient(135deg,#26122f,#120b18);">
                                        <p style="margin:0 0 18px; color:#d96eea; font-size:12px; line-height:18px; letter-spacing:4px; text-transform:uppercase;">Tu historia importa</p>
                                        <h1 style="margin:0; color:#fbf8fc; font-family:Georgia,'Times New Roman',serif; font-size:42px; font-weight:400; line-height:1.02; letter-spacing:-1.6px;">Gracias por<br>ser parte de <span style="color:#c23bea;">Velvet.</span></h1>
                                        <p style="margin:24px 0 0; color:#c4beca; font-size:16px; line-height:25px;">Hemos recibido tu aplicación y ya está en proceso de revisión. Nos emociona que quieras ser parte de nuestra comunidad.</p>
                                    </td>
                                    <td class="email-hero-image" width="46%" valign="bottom" style="width:46%; background:#170b20;">
                                        <img src="{{ $heroUrl }}" width="300" height="360" alt="Talento Velvet" style="display:block; width:100%; max-width:100%; height:360px; object-fit:cover; object-position:50% 35%; border:0; opacity:.82;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:28px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #34313d; border-radius:9px; background:#0e1118;">
                                <tr>
                                    <td class="email-id-cell" width="30%" align="center" style="width:30%; padding:28px 10px;">
                                        <div style="display:inline-block; width:70px; height:70px; border:1px solid #c23bea; border-radius:50%; color:#e0a0ed; font-size:26px; line-height:70px; text-align:center;">ID</div>
                                    </td>
                                    <td class="email-code-cell" width="70%" style="width:70%; padding:24px 20px 24px 4px;">
                                        <p style="margin:0 0 12px; color:#d96eea; font-size:12px; line-height:18px; letter-spacing:3px; text-transform:uppercase;">Tu código de aplicación</p>
                                        <div class="email-code" style="border:1px solid #883499; border-radius:6px; padding:13px 10px; color:#d9a2eb; font-family:Consolas,'Courier New',monospace; font-size:28px; line-height:35px; letter-spacing:4px; text-align:center;">{{ $lead->code }}</div>
                                        <p style="margin:12px 0 0; color:#aaa5b2; font-size:13px; line-height:20px;">Guarda este código. Lo usaremos como referencia en futuras comunicaciones.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:34px 30px 0;">
                            <h2 style="margin:0; color:#fbf8fc; font-family:Georgia,'Times New Roman',serif; font-size:31px; font-weight:400; line-height:38px;">¿Qué sigue ahora?</h2>
                            <p style="margin:9px 0 0; color:#b7b1bf; font-size:15px; line-height:24px;">Nuestro equipo está revisando tu información cuidadosamente.<br>Te contactaremos muy pronto para contarte los siguientes pasos.</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:20px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-step" width="33.33%" valign="top" style="width:33.33%; padding:14px 15px 12px 0; border-right:1px solid #292936; text-align:center;">
                                        <div style="display:inline-block; width:38px; height:38px; border:1px solid #883499; border-radius:50%; color:#d96eea; font-size:13px; line-height:38px;">01</div>
                                        <p style="margin:13px 0 0; color:#f7f1fb; font-size:15px; font-weight:bold; line-height:20px;">Revisión de tu perfil</p>
                                        <p style="margin:6px 0 0; color:#aaa5b2; font-size:13px; line-height:20px;">Evaluaremos tu información y experiencia.</p>
                                    </td>
                                    <td class="email-step" width="33.33%" valign="top" style="width:33.33%; padding:14px 15px 12px; border-right:1px solid #292936; text-align:center;">
                                        <div style="display:inline-block; width:38px; height:38px; border:1px solid #883499; border-radius:50%; color:#d96eea; font-size:13px; line-height:38px;">02</div>
                                        <p style="margin:13px 0 0; color:#f7f1fb; font-size:15px; font-weight:bold; line-height:20px;">Nos pondremos en contacto</p>
                                        <p style="margin:6px 0 0; color:#aaa5b2; font-size:13px; line-height:20px;">Te escribiremos por correo o WhatsApp.</p>
                                    </td>
                                    <td class="email-step" width="33.33%" valign="top" style="width:33.33%; padding:14px 0 12px 15px; text-align:center;">
                                        <div style="display:inline-block; width:38px; height:38px; border:1px solid #883499; border-radius:50%; color:#d96eea; font-size:13px; line-height:38px;">03</div>
                                        <p style="margin:13px 0 0; color:#f7f1fb; font-size:15px; font-weight:bold; line-height:20px;">Mantente atento(a)</p>
                                        <p style="margin:6px 0 0; color:#aaa5b2; font-size:13px; line-height:20px;">Revisa tu bandeja y evita correos no deseados.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:28px 30px 0;">
                            <div style="height:1px; background:#883499;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td class="email-gutter" style="padding:28px 30px 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #34313d; border-radius:9px; background:#0e1118;">
                                <tr>
                                    <td width="20%" align="center" style="width:20%; padding:20px 8px;"><div style="display:inline-block; width:48px; height:48px; border:1px solid #c23bea; border-radius:50%; color:#e0a0ed; font-size:25px; line-height:48px;">♥</div></td>
                                    <td width="80%" style="width:80%; padding:18px 12px 18px 0;"><p style="margin:0; color:#f7f1fb; font-size:16px; font-weight:bold; line-height:22px;">Gracias por confiar en Velvet.</p><p style="margin:4px 0 0; color:#aaa5b2; font-size:14px; line-height:21px;">Aquí comienzan grandes historias.</p></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:32px 30px 12px; color:#a9a3b1; font-size:13px; letter-spacing:4px;">REAL PEOPLE. BIGGER STORIES.</td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 30px 30px; color:#777380; font-size:12px; line-height:20px;">The Velvet Studio · {{ $typeLabel }} · {{ $submittedLabel }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

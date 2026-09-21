@php
    $logoUrl = asset('THEVELVETSTUDIO_BRANDBOOK-55.png');
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu proceso avanzó · The Velvet Studio</title>
</head>
<body style="margin:0; padding:0; background:#100b18; color:#f7f1fb; font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#100b18; padding:28px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; background:#080a10; border:1px solid #292936; border-radius:14px; overflow:hidden;">
                    <tr><td align="center" style="padding:28px 30px 20px;"><img src="{{ $logoUrl }}" width="190" alt="The Velvet Studio" style="display:block; width:190px; max-width:100%; height:auto; border:0;"></td></tr>
                    <tr><td style="padding:30px 34px 22px; background:linear-gradient(135deg,#26122f,#120b18);"><p style="margin:0 0 12px; color:#d96eea; font-size:12px; letter-spacing:3px; text-transform:uppercase;">Proceso actualizado</p><h1 style="margin:0; color:#fbf8fc; font-family:Georgia,'Times New Roman',serif; font-size:36px; font-weight:400; line-height:1.08;">Hola, {{ $applicantName }}.</h1><p style="margin:20px 0 0; color:#c4beca; font-size:16px; line-height:25px;">Recibimos tus requisitos iniciales y tu perfil avanzó a la etapa de revisión por nuestro equipo.</p></td></tr>
                    <tr><td style="padding:28px 34px 8px;"><p style="margin:0; color:#aaa5b2; font-size:13px; line-height:21px;">Código de candidata: <strong style="color:#e0a0ed;">{{ $candidateCode }}</strong></p><p style="margin:22px 0 0; color:#c4beca; font-size:15px; line-height:24px;">Tu verificación de identidad fue recibida correctamente y la información de precalificación quedó registrada.</p></td></tr>
                    <tr><td style="padding:22px 34px 14px;"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #343044; border-radius:10px; background:#151522;"><tr><td style="padding:18px 20px;"><p style="margin:0 0 12px; color:#e5a1f2; font-size:12px; letter-spacing:2px; text-transform:uppercase;">Estado del proceso</p><p style="margin:0 0 9px; color:#9af2cb; font-size:15px;">✓ Aplicación recibida</p><p style="margin:0 0 9px; color:#9af2cb; font-size:15px;">✓ Identidad verificada</p><p style="margin:0; color:#9af2cb; font-size:15px;">✓ Requisitos iniciales recibidos</p></td></tr></table></td></tr>
                    <tr><td style="padding:14px 34px 32px; color:#898291; font-size:12px; line-height:20px;">Nuestro equipo revisará tu perfil y te contactará por este mismo correo con los siguientes pasos. No necesitas enviar nuevamente la información.</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle connexion</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #000080 0%, #0000a0 100%); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700;">🔐 Alerte de Sécurité</h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">KSSV Admin</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{{ $user->name }}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 24px 0; color: #555555; font-size: 16px; line-height: 1.6;">
                                Une nouvelle connexion a été détectée sur votre compte administrateur KSSV.
                            </p>
                            
                            <!-- Details Box -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa; border-radius: 12px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <h3 style="margin: 0 0 16px 0; color: #000080; font-size: 16px; font-weight: 600;">Détails de la connexion</h3>
                                        
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 120px;">📅 Date/Heure</td>
                                                <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $loginTime }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-size: 14px;">🌐 Adresse IP</td>
                                                <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $ipAddress }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px 0; color: #666666; font-size: 14px;">💻 Navigateur</td>
                                                <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ Str::limit($userAgent, 60) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Warning Box -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fff3e6; border-left: 4px solid #fd7f07; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.5;">
                                            ⚠️ <strong>Si vous n'êtes pas à l'origine de cette connexion</strong>, veuillez contacter immédiatement l'équipe technique et changer votre mot de passe.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 0; color: #666666; font-size: 14px; line-height: 1.6;">
                                Si c'est bien vous, vous pouvez ignorer cet email. Cette notification est envoyée automatiquement pour protéger votre compte.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 24px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 8px 0; color: #000080; font-size: 14px; font-weight: 600;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                Cet email a été envoyé automatiquement. Ne pas répondre.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

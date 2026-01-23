<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de vérification KSSV</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #B8860B 0%, #DAA520 50%, #FFD700 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                Keur Serigne Saliou Vaisselle
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                KSSV - Votre référence en vaisselle
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; color: #1a1a1a; font-size: 24px; font-weight: 600;">
                                @if($type === 'registration')
                                    Vérifiez votre adresse email
                                @else
                                    Réinitialisez votre mot de passe
                                @endif
                            </h2>
                            
                            <p style="margin: 0 0 30px; color: #666666; font-size: 16px; line-height: 1.6;">
                                @if($type === 'registration')
                                    Bienvenue ! Pour finaliser votre inscription sur KSSV, veuillez utiliser le code de vérification ci-dessous :
                                @else
                                    Vous avez demandé à réinitialiser votre mot de passe. Utilisez le code ci-dessous pour continuer :
                                @endif
                            </p>

                            <!-- OTP Code Box -->
                            <div style="text-align: center; margin: 30px 0;">
                                <div style="display: inline-block; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #B8860B; border-radius: 12px; padding: 20px 40px;">
                                    <span style="font-family: 'Courier New', monospace; font-size: 36px; font-weight: 700; letter-spacing: 8px; color: #B8860B;">
                                        {{ $code }}
                                    </span>
                                </div>
                            </div>

                            <p style="margin: 30px 0 0; color: #999999; font-size: 14px; text-align: center;">
                                ⏱️ Ce code expire dans <strong>10 minutes</strong>
                            </p>

                            <!-- Security Notice -->
                            <div style="margin-top: 30px; padding: 20px; background-color: #fff8e1; border-left: 4px solid #B8860B; border-radius: 4px;">
                                <p style="margin: 0; color: #666666; font-size: 14px;">
                                    <strong>🔒 Conseil de sécurité :</strong><br>
                                    Ne partagez jamais ce code avec quelqu'un d'autre. L'équipe KSSV ne vous demandera jamais votre code par téléphone ou par message.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0 0 10px; color: #999999; font-size: 14px;">
                                Si vous n'avez pas demandé ce code, ignorez simplement cet email.
                            </p>
                            <p style="margin: 0; color: #B8860B; font-size: 14px; font-weight: 600;">
                                © {{ date('Y') }} KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 10px 0 0; color: #999999; font-size: 12px;">
                                Dakar, Sénégal | contact@kssv.sn
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Désactivé</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #000080 0%, #0000a0 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
                                🔒 KSSV Admin
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{{ $user->name }}</strong>,
                            </p>
                            
                            <!-- Alert Box -->
                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px 20px; border-radius: 0 8px 8px 0; margin: 20px 0;">
                                <p style="margin: 0; color: #991b1b; font-weight: 500; font-size: 15px;">
                                    ⚠️ Votre accès administrateur a été désactivé.
                                </p>
                            </div>
                            
                            <p style="margin: 0 0 24px; color: #555555; font-size: 16px; line-height: 1.6;">
                                Nous vous informons que votre compte administrateur sur la plateforme KSSV a été désactivé par un super administrateur.
                            </p>
                            
                            <!-- Info Box -->
                            <div style="background-color: #f0f4ff; border-radius: 12px; padding: 20px; margin: 20px 0;">
                                <p style="margin: 0 0 12px; color: #000080; font-weight: 600; font-size: 14px;">Conséquences :</p>
                                <ul style="margin: 0; padding-left: 20px; color: #555555; font-size: 14px; line-height: 1.8;">
                                    <li>Vous ne pouvez plus vous connecter à l'espace administration</li>
                                    <li>Vos sessions actives ont été révoquées</li>
                                    <li>Votre historique d'actions reste conservé</li>
                                </ul>
                            </div>
                            
                            <p style="margin: 24px 0; color: #555555; font-size: 16px; line-height: 1.6;">
                                Si vous pensez qu'il s'agit d'une erreur ou si vous souhaitez obtenir plus d'informations, veuillez contacter l'administrateur principal.
                            </p>
                            
                            <!-- Contact Box -->
                            <div style="background-color: #fff3e6; border-radius: 12px; padding: 20px; margin-top: 20px;">
                                <p style="margin: 0 0 8px; color: #333333; font-weight: 600; font-size: 14px;">📞 Contact :</p>
                                <p style="margin: 5px 0; color: #555555; font-size: 14px;">📧 contact@kssv.sn</p>
                                <p style="margin: 5px 0; color: #555555; font-size: 14px;">📱 +221 76 644 16 71</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; color: #000080; font-size: 14px; font-weight: 600;">
                                KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 8px 0 0; color: #666666; font-size: 13px;">
                                Dakar, Sénégal
                            </p>
                            <p style="margin: 8px 0 0; color: #999999; font-size: 12px;">
                                Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

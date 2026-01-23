<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Admin - KSSV</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f5f5f5;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #D4AF37 0%, #C49B2B 100%); padding: 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">
                                🎉 Bienvenue chez KSSV
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px; color: #333333; font-size: 22px;">
                                Bonjour {{ $admin->name }} !
                            </h2>
                            
                            <p style="margin: 0 0 20px; color: #555555; font-size: 16px; line-height: 1.6;">
                                Vous avez été invité à rejoindre l'équipe d'administration de <strong>KSSV - Keur Serigne Saliou Vaisselle</strong>.
                            </p>
                            
                            <p style="margin: 0 0 30px; color: #555555; font-size: 16px; line-height: 1.6;">
                                Cliquez sur le bouton ci-dessous pour activer votre compte et définir votre mot de passe :
                            </p>
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="border-radius: 8px; background: linear-gradient(135deg, #D4AF37 0%, #C49B2B 100%);">
                                        <a href="{{ config('app.frontend_url') }}/admin/activate?token={{ $activationToken }}&email={{ urlencode($admin->email) }}" 
                                           target="_blank"
                                           style="display: inline-block; padding: 16px 40px; color: #ffffff; text-decoration: none; font-size: 16px; font-weight: bold; border-radius: 8px;">
                                            ✨ Activer mon compte
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 30px 0 0; color: #888888; font-size: 14px; text-align: center;">
                                ⏰ Ce lien expire dans <strong>72 heures</strong>.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Info Box -->
                    <tr>
                        <td style="padding: 0 30px 30px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 10px; color: #555555; font-size: 14px; font-weight: bold;">
                                            📋 En tant qu'administrateur, vous pourrez :
                                        </p>
                                        <ul style="margin: 0; padding-left: 20px; color: #666666; font-size: 14px; line-height: 1.8;">
                                            <li>Gérer les commandes et les clients</li>
                                            <li>Modifier l'inventaire et les prix</li>
                                            <li>Configurer les codes promo</li>
                                            <li>Accéder aux statistiques</li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0 0 5px; color: #888888; font-size: 12px;">
                                KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 0; color: #888888; font-size: 12px;">
                                📞 +221 76 644 16 71 | 📍 Dakar, Sénégal
                            </p>
                        </td>
                    </tr>
                </table>
                
                <!-- Disclaimer -->
                <p style="max-width: 600px; margin: 20px auto 0; color: #999999; font-size: 11px; text-align: center;">
                    Si vous n'avez pas demandé cette invitation, veuillez ignorer cet email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>

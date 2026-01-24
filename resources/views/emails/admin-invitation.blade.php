<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Administrateur KSSV</title>
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
                                🎉 Bienvenue dans l'équipe KSSV
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Invitation Administrateur
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{{ $admin->name }}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 30px; color: #555555; font-size: 16px; line-height: 1.6;">
                                Vous avez été invité(e) à rejoindre l'équipe d'administration de <strong>Keur Serigne Saliou Vaisselle</strong>.
                                Cliquez sur le bouton ci-dessous pour activer votre compte et définir votre mot de passe.
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <table role="presentation" style="border-collapse: collapse;">
                                            <tr>
                                                <td style="border-radius: 8px; background: linear-gradient(135deg, #fd7f07 0%, #e56b00 100%);">
                                                    <a href="{{ $frontendUrl }}/admin/activate?token={{ $activationToken }}&email={{ urlencode($admin->email) }}"
                                                       target="_blank"
                                                       style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">
                                                        Activer mon compte
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Info Box -->
                            <div style="margin-top: 30px; padding: 20px; background-color: #f0f4ff; border-left: 4px solid #000080; border-radius: 4px;">
                                <p style="margin: 0 0 10px; color: #333333; font-size: 14px; font-weight: 600;">
                                    En tant qu'administrateur, vous pourrez :
                                </p>
                                <ul style="margin: 0; padding-left: 20px; color: #555555; font-size: 14px; line-height: 1.8;">
                                    <li>Gérer les commandes et les paiements</li>
                                    <li>Administrer le catalogue de produits</li>
                                    <li>Consulter les statistiques de vente</li>
                                    <li>Gérer les clients et les promotions</li>
                                </ul>
                            </div>

                            <p style="margin: 30px 0 0; color: #999999; font-size: 13px; text-align: center;">
                                Ce lien expire dans 48 heures. Si vous n'avez pas demandé cette invitation, veuillez ignorer cet email.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; color: #000080; font-size: 14px; font-weight: 600;">
                                © {{ date('Y') }} KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 10px 0 0; color: #999999; font-size: 12px;">
                                Dakar, Sénégal | Cet email a été envoyé automatiquement
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

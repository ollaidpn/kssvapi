<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue chez KSSV</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa;">
    <table role="presentation" style="width: 100%; border-collapse: collapse;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" style="width: 100%; max-width: 600px; border-collapse: collapse; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #000080 0%, #0000a0 100%); border-radius: 16px 16px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">
                                🎉 Bienvenue !
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 18px; line-height: 1.6;">
                                Bonjour <strong>{{ $user->name }}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 30px; color: #555555; font-size: 16px; line-height: 1.6;">
                                Nous sommes ravis de vous accueillir parmi nos clients ! Votre compte a été créé avec succès sur notre plateforme.
                            </p>

                            <!-- Account Info Box -->
                            <div style="background-color: #f8f9fa; border-radius: 12px; padding: 24px; margin-bottom: 30px;">
                                <h3 style="margin: 0 0 16px; color: #000080; font-size: 16px; font-weight: 600;">
                                    📋 Vos informations
                                </h3>
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px; width: 140px;">Référence client</td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $user->reference }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">Email</td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">Téléphone</td>
                                        <td style="padding: 8px 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $user->ccphone }} {{ $user->phone }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Benefits -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="margin: 0 0 16px; color: #333333; font-size: 16px; font-weight: 600;">
                                    ✨ Ce que vous pouvez faire
                                </h3>
                                <ul style="margin: 0; padding-left: 20px; color: #555555; font-size: 14px; line-height: 2;">
                                    <li>Parcourir notre catalogue de vaisselle et articles ménagers</li>
                                    <li>Passer des commandes en ligne en toute sécurité</li>
                                    <li>Suivre vos commandes en temps réel</li>
                                    <li>Bénéficier de promotions exclusives</li>
                                </ul>
                            </div>

                            <!-- CTA Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center" style="padding: 10px 0;">
                                        <table role="presentation" style="border-collapse: collapse;">
                                            <tr>
                                                <td style="border-radius: 8px; background: linear-gradient(135deg, #fd7f07 0%, #e56b00 100%);">
                                                    <a href="{{ config('app.frontend_url') }}"
                                                       target="_blank"
                                                       style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">
                                                        Découvrir nos produits
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Contact Section -->
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <div style="background-color: #fff3e6; border-radius: 12px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 10px; color: #333333; font-size: 14px; font-weight: 600;">
                                    📞 Besoin d'aide ?
                                </p>
                                <p style="margin: 0; color: #555555; font-size: 14px;">
                                    Contactez-nous au <strong>+221 76 644 16 71</strong><br>
                                    ou par email : <strong>contact@kssv.sn</strong>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; color: #000080; font-size: 14px; font-weight: 600;">
                                © {{ date('Y') }} KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 10px 0 0; color: #999999; font-size: 12px;">
                                Dakar, Sénégal
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

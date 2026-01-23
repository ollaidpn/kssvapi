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
                    <!-- Header with celebration -->
                    <tr>
                        <td style="padding: 40px 40px 20px; text-align: center; background: linear-gradient(135deg, #B8860B 0%, #DAA520 50%, #FFD700 100%); border-radius: 16px 16px 0 0;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🎉</div>
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                Bienvenue chez KSSV !
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="margin: 0 0 20px; color: #1a1a1a; font-size: 22px; font-weight: 600;">
                                Bonjour {{ $user->name }} 👋
                            </h2>
                            
                            <p style="margin: 0 0 20px; color: #666666; font-size: 16px; line-height: 1.6;">
                                Nous sommes ravis de vous compter parmi notre famille KSSV ! Votre compte a été créé avec succès.
                            </p>

                            <!-- Account Info -->
                            <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; padding: 25px; margin: 25px 0;">
                                <h3 style="margin: 0 0 15px; color: #1a1a1a; font-size: 16px; font-weight: 600;">
                                    📋 Vos informations de compte
                                </h3>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; color: #999999; font-size: 14px;">Référence client :</td>
                                        <td style="padding: 8px 0; color: #B8860B; font-size: 14px; font-weight: 600; text-align: right;">{{ $user->reference }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #999999; font-size: 14px;">Email :</td>
                                        <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; text-align: right;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #999999; font-size: 14px;">Téléphone :</td>
                                        <td style="padding: 8px 0; color: #1a1a1a; font-size: 14px; text-align: right;">+{{ $user->ccphone }} {{ $user->phone }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Benefits -->
                            <h3 style="margin: 30px 0 20px; color: #1a1a1a; font-size: 18px; font-weight: 600;">
                                ✨ Ce que vous pouvez faire maintenant
                            </h3>
                            
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 12px 0; vertical-align: top;">
                                        <span style="display: inline-block; width: 32px; height: 32px; background-color: #fff8e1; border-radius: 50%; text-align: center; line-height: 32px; margin-right: 12px;">🛒</span>
                                    </td>
                                    <td style="padding: 12px 0; color: #666666; font-size: 14px; line-height: 1.5;">
                                        <strong style="color: #1a1a1a;">Commander en ligne</strong><br>
                                        Parcourez notre catalogue et commandez en quelques clics
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; vertical-align: top;">
                                        <span style="display: inline-block; width: 32px; height: 32px; background-color: #fff8e1; border-radius: 50%; text-align: center; line-height: 32px; margin-right: 12px;">📦</span>
                                    </td>
                                    <td style="padding: 12px 0; color: #666666; font-size: 14px; line-height: 1.5;">
                                        <strong style="color: #1a1a1a;">Suivre vos commandes</strong><br>
                                        Accédez à l'historique et au suivi de toutes vos commandes
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; vertical-align: top;">
                                        <span style="display: inline-block; width: 32px; height: 32px; background-color: #fff8e1; border-radius: 50%; text-align: center; line-height: 32px; margin-right: 12px;">🎁</span>
                                    </td>
                                    <td style="padding: 12px 0; color: #666666; font-size: 14px; line-height: 1.5;">
                                        <strong style="color: #1a1a1a;">Offres exclusives</strong><br>
                                        Profitez de promotions réservées à nos membres
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 35px 0 20px;">
                                <a href="{{ config('app.url') }}" style="display: inline-block; background: linear-gradient(135deg, #B8860B 0%, #DAA520 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(184, 134, 11, 0.3);">
                                    Commencer mes achats →
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Contact -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #1a1a1a; text-align: center;">
                            <p style="margin: 0 0 15px; color: #ffffff; font-size: 16px; font-weight: 600;">
                                Besoin d'aide ?
                            </p>
                            <p style="margin: 0 0 5px; color: #999999; font-size: 14px;">
                                📞 +221 XX XXX XX XX
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 14px;">
                                ✉️ contact@kssv.sn
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0 0 10px; color: #B8860B; font-size: 14px; font-weight: 600;">
                                © {{ date('Y') }} KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 0; color: #999999; font-size: 12px;">
                                Dakar, Sénégal | La qualité au service de votre table
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

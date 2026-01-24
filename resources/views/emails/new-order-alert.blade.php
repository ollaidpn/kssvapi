<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Commande</title>
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
                                🛒 Nouvelle Commande !
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Alerte Admin KSSV
                            </p>
                        </td>
                    </tr>

                    <!-- Reference -->
                    <tr>
                        <td style="padding: 30px 40px 20px;">
                            <div style="background-color: #fff3e6; border: 2px dashed #fd7f07; border-radius: 12px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 8px; color: #666666; font-size: 14px;">Référence</p>
                                <p style="margin: 0; color: #fd7f07; font-size: 28px; font-weight: 700; letter-spacing: 2px;">{{ $order->reference }}</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <!-- Order Details Table -->
                            <div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                                <div style="background-color: #f8f9fa; padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                    <h3 style="margin: 0; color: #333333; font-size: 16px; font-weight: 600;">📋 Détails de la commande</h3>
                                </div>
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px; width: 140px;">Client</td>
                                        <td style="padding: 12px 20px; color: #333333; font-size: 14px; font-weight: 500;">{{ $client->name }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px;">Email</td>
                                        <td style="padding: 12px 20px; color: #333333; font-size: 14px;">{{ $client->email }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px;">Téléphone</td>
                                        <td style="padding: 12px 20px; color: #333333; font-size: 14px; font-weight: 500;">{{ $client->ccphone ?? '' }} {{ $client->phone ?? 'Non renseigné' }}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px;">Articles</td>
                                        <td style="padding: 12px 20px; color: #333333; font-size: 14px; font-weight: 500;">{{ $itemsCount }} article(s)</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px;">Paiement</td>
                                        <td style="padding: 12px 20px;">
                                            @if($paymentMethod === 'cash_on_delivery')
                                                <span style="display: inline-block; background-color: #fff3e6; color: #fd7f07; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">À la livraison</span>
                                            @elseif($paymentMethod === 'wave_senegal')
                                                <span style="display: inline-block; background-color: #e6f7ff; color: #0066cc; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Wave</span>
                                            @else
                                                <span style="display: inline-block; background-color: #fff3e6; color: #fd7f07; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Orange Money</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 12px 20px; color: #666666; font-size: 14px;">Livraison</td>
                                        <td style="padding: 12px 20px; color: #333333; font-size: 14px;">{{ $order->address }}, {{ $order->city }}</td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Total -->
                            <div style="background: linear-gradient(135deg, #000080 0%, #0000a0 100%); border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 24px;">
                                <p style="margin: 0 0 8px; color: rgba(255,255,255,0.8); font-size: 14px;">Montant total</p>
                                <p style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700;">{{ number_format($order->total, 0, '', ' ') }} FCFA</p>
                            </div>

                            <!-- CTA Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" style="border-collapse: collapse;">
                                            <tr>
                                                <td style="border-radius: 8px; background: linear-gradient(135deg, #fd7f07 0%, #e56b00 100%);">
                                                    <a href="{{ config('app.frontend_url') }}/admin"
                                                       target="_blank"
                                                       style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">
                                                        Voir dans l'admin
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; color: #000080; font-size: 14px; font-weight: 600;">
                                KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 8px 0 0; color: #999999; font-size: 12px;">
                                Notification automatique de nouvelle commande
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

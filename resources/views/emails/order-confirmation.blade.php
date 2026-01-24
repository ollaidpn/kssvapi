<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
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
                                @if($isPaid)
                                    ✅ Commande Confirmée et Payée
                                @else
                                    📦 Commande Confirmée
                                @endif
                            </h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">
                                Keur Serigne Saliou Vaisselle
                            </p>
                        </td>
                    </tr>

                    <!-- Reference & Status -->
                    <tr>
                        <td style="padding: 30px 40px 20px;">
                            <div style="background-color: #f8f9fa; border-radius: 12px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 8px; color: #666666; font-size: 14px;">Référence de commande</p>
                                <p style="margin: 0; color: #000080; font-size: 24px; font-weight: 700; letter-spacing: 2px;">{{ $order->reference }}</p>
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <p style="margin: 0 0 20px; color: #333333; font-size: 16px; line-height: 1.6;">
                                Bonjour <strong>{{ $user->name }}</strong>,
                            </p>
                            
                            <p style="margin: 0 0 24px; color: #555555; font-size: 16px; line-height: 1.6;">
                                @if($isPaid)
                                    Merci pour votre commande ! Votre paiement a bien été reçu et votre commande est en cours de préparation.
                                @else
                                    Merci pour votre commande ! Elle a bien été enregistrée et sera préparée dès réception du paiement.
                                @endif
                            </p>

                            <!-- Order Details -->
                            <div style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                                <div style="background-color: #f8f9fa; padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                    <h3 style="margin: 0; color: #333333; font-size: 16px; font-weight: 600;">📋 Détails de la commande</h3>
                                </div>
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    @foreach($items as $item)
                                        @php
                                            $itemInfos = is_string($item->item_infos) ? json_decode($item->item_infos, true) : $item->item_infos;
                                            $itemName = $itemInfos['name'] ?? 'Article';
                                        @endphp
                                        <tr style="border-bottom: 1px solid #f3f4f6;">
                                            <td style="padding: 16px 20px;">
                                                <p style="margin: 0; color: #333333; font-size: 14px; font-weight: 500;">{{ $itemName }}</p>
                                                <p style="margin: 4px 0 0; color: #666666; font-size: 13px;">Qté: {{ $item->qty }} × {{ number_format($item->price, 0, '', ' ') }} FCFA</p>
                                            </td>
                                            <td style="padding: 16px 20px; text-align: right;">
                                                <p style="margin: 0; color: #333333; font-size: 14px; font-weight: 600;">{{ number_format($item->price * $item->qty, 0, '', ' ') }} FCFA</p>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                                
                                <!-- Totals -->
                                <div style="background-color: #f8f9fa; padding: 16px 20px;">
                                    @php
                                        $subtotal = $order->total + $order->discount - ($order->delivery_fee ?? 0);
                                    @endphp
                                    <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                        <tr>
                                            <td style="padding: 4px 0; color: #666666; font-size: 14px;">Sous-total</td>
                                            <td style="padding: 4px 0; text-align: right; color: #333333; font-size: 14px;">{{ number_format($subtotal, 0, '', ' ') }} FCFA</td>
                                        </tr>
                                        @if($order->discount > 0)
                                        <tr>
                                            <td style="padding: 4px 0; color: #16a34a; font-size: 14px;">Réduction</td>
                                            <td style="padding: 4px 0; text-align: right; color: #16a34a; font-size: 14px;">-{{ number_format($order->discount, 0, '', ' ') }} FCFA</td>
                                        </tr>
                                        @endif
                                        @if(($order->delivery_fee ?? 0) > 0)
                                        <tr>
                                            <td style="padding: 4px 0; color: #fd7f07; font-size: 14px;">Frais de livraison</td>
                                            <td style="padding: 4px 0; text-align: right; color: #fd7f07; font-size: 14px;">+{{ number_format($order->delivery_fee, 0, '', ' ') }} FCFA</td>
                                        </tr>
                                        @endif
                                    </table>
                                    <table role="presentation" style="width: 100%; border-collapse: collapse; margin-top: 8px;">
                                        <tr>
                                            <td style="padding: 8px 0; color: #000080; font-size: 18px; font-weight: 700;">Total</td>
                                            <td style="padding: 8px 0; text-align: right; color: #000080; font-size: 18px; font-weight: 700;">{{ number_format($order->total, 0, '', ' ') }} FCFA</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Delivery Address -->
                            @if($order->address)
                            <div style="background-color: #f0f4ff; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                                <h4 style="margin: 0 0 12px; color: #000080; font-size: 14px; font-weight: 600;">🚚 Adresse de livraison</h4>
                                <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                    {{ $order->address }}<br>
                                    {{ $order->city }}
                                </p>
                            </div>
                            @endif

                            <!-- Payment Instructions (if not paid) -->
                            @if(!$isPaid)
                            <div style="background-color: #fff3e6; border-left: 4px solid #fd7f07; border-radius: 0 12px 12px 0; padding: 20px; margin-bottom: 24px;">
                                <h4 style="margin: 0 0 12px; color: #fd7f07; font-size: 14px; font-weight: 600;">💳 Instructions de paiement</h4>
                                <p style="margin: 0; color: #555555; font-size: 14px; line-height: 1.6;">
                                    Le paiement sera effectué à la livraison. Notre livreur vous contactera prochainement pour convenir d'un rendez-vous.
                                </p>
                            </div>
                            @endif

                            <!-- CTA Button -->
                            <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" style="border-collapse: collapse;">
                                            <tr>
                                                <td style="border-radius: 8px; background: linear-gradient(135deg, #fd7f07 0%, #e56b00 100%);">
                                                    <a href="{{ $frontendUrl }}/account"
                                                       target="_blank"
                                                       style="display: inline-block; padding: 16px 40px; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none;">
                                                        Suivre ma commande
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
                    @if($appInfo)
                    <tr>
                        <td style="padding: 0 40px 30px;">
                            <div style="background-color: #f8f9fa; border-radius: 12px; padding: 20px; text-align: center;">
                                <p style="margin: 0 0 8px; color: #333333; font-size: 14px; font-weight: 600;">📞 Des questions ?</p>
                                <p style="margin: 0; color: #555555; font-size: 14px;">
                                    @if($appInfo->phone1)
                                        Contactez-nous au <strong>{{ $appInfo->ccphone1 ?? '' }} {{ $appInfo->phone1 }}</strong>
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px 40px; background-color: #f8f9fa; border-radius: 0 0 16px 16px; text-align: center;">
                            <p style="margin: 0; color: #000080; font-size: 14px; font-weight: 600;">
                                Merci pour votre confiance !
                            </p>
                            <p style="margin: 8px 0 0; color: #666666; font-size: 13px;">
                                © {{ date('Y') }} KSSV - Keur Serigne Saliou Vaisselle
                            </p>
                            <p style="margin: 8px 0 0; color: #999999; font-size: 12px;">
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

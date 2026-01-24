<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Désactivé</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #000080;
        }
        .header img {
            max-width: 80px;
            margin-bottom: 10px;
        }
        .header h1 {
            color: #000080;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .alert-box {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        .alert-box p {
            margin: 0;
            color: #991b1b;
            font-weight: 500;
        }
        .info-box {
            background-color: #f0f9ff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e5e5;
            color: #666;
            font-size: 14px;
        }
        .contact-info {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 KSSV Admin</h1>
            <p style="color: #666; margin: 5px 0 0;">Keur Serigne Saliou Vaisselle</p>
        </div>
        
        <div class="content">
            <p>Bonjour <strong>{{ $user->name }}</strong>,</p>
            
            <div class="alert-box">
                <p>⚠️ Votre accès administrateur a été désactivé.</p>
            </div>
            
            <p>Nous vous informons que votre compte administrateur sur la plateforme KSSV a été désactivé par un super administrateur.</p>
            
            <div class="info-box">
                <p><strong>Conséquences :</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Vous ne pouvez plus vous connecter à l'espace administration</li>
                    <li>Vos sessions actives ont été révoquées</li>
                    <li>Votre historique d'actions reste conservé</li>
                </ul>
            </div>
            
            <p>Si vous pensez qu'il s'agit d'une erreur ou si vous souhaitez obtenir plus d'informations, veuillez contacter l'administrateur principal.</p>
            
            <div class="contact-info">
                <p style="margin: 0;"><strong>Contact :</strong></p>
                <p style="margin: 5px 0;">📧 contact@kssv.sn</p>
                <p style="margin: 5px 0;">📞 +221 76 644 16 71</p>
            </div>
        </div>
        
        <div class="footer">
            <p>KSSV - Keur Serigne Saliou Vaisselle</p>
            <p>Dakar, Sénégal</p>
            <p style="font-size: 12px; color: #999;">Cet email a été envoyé automatiquement. Merci de ne pas y répondre directement.</p>
        </div>
    </div>
</body>
</html>

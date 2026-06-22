<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product['name'] }} - {{ number_format($product['price'], 0, ',', '.') }} FCFA | KSSV</title>
    <meta name="description" content="{{ $product['name'] }} (Code: {{ $product['code'] }}) disponible chez KSSV - Keur Serigne Saliou Vaisselle.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="{{ $product['name'] }} - {{ number_format($product['price'], 0, ',', '.') }} FCFA">
    <meta property="og:description" content="{{ $product['name'] }} disponible chez KSSV - Keur Serigne Saliou Vaisselle.">
    <meta property="og:image" content="{{ $product['image'] }}">
    <meta property="og:url" content="{{ config('app.frontend_website_endpoint', config('app.frontend_url')) }}/produits/{{ $product['id'] }}">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="KSSV - Keur Serigne Saliou Vaisselle">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $product['name'] }} - {{ number_format($product['price'], 0, ',', '.') }} FCFA">
    <meta name="twitter:description" content="{{ $product['name'] }} disponible chez KSSV.">
    <meta name="twitter:image" content="{{ $product['image'] }}">
    
    <!-- Product Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": "{{ $product['name'] }}",
        "image": "{{ $product['image'] }}",
        "sku": "{{ $product['code'] }}",
        "offers": {
            "@type": "Offer",
            "price": "{{ $product['price'] }}",
            "priceCurrency": "XOF",
            "availability": "https://schema.org/InStock"
        }
    }
    </script>
    
    <!-- Redirection vers l'app React -->
    <script>
        window.location.href = "{{ config('app.frontend_website_endpoint', config('app.frontend_url')) }}/produits/{{ $product['id'] }}";
    </script>
</head>
<body style="font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5;">
    <div style="text-align: center;">
        <img src="{{ asset('logo.png') }}" alt="KSSV" style="width: 120px; margin-bottom: 20px;">
        <p style="color: #666;">Redirection en cours...</p>
    </div>
</body>
</html>

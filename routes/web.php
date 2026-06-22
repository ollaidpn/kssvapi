<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogViewerController;
use App\Models\Item;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route pour les previews Open Graph (crawlers WhatsApp, Telegram, Facebook, etc.)
Route::get('/produits/{id}', function ($id) {
    $product = Item::find($id);
    
    if (!$product) {
        return redirect(config('app.frontend_website_endpoint', config('app.frontend_url')) . '/produits/' . $id);
    }
    
    // Détecter si c'est un bot/crawler
    $userAgent = request()->header('User-Agent', '');
    $isCrawler = preg_match('/(facebookexternalhit|Twitterbot|WhatsApp|TelegramBot|LinkedInBot|Slackbot|Googlebot|bingbot)/i', $userAgent);
    
    if ($isCrawler) {
        // Retourner la vue avec les meta tags pour les crawlers
        return view('product-preview', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'price' => $product->price,
                'image' => $product->image ?: asset('no-image.png'),
            ]
        ]);
    }
    
    // Sinon, rediriger vers le frontend React
    return redirect(config('app.frontend_website_endpoint', config('app.frontend_url')) . '/produits/' . $id);
});

// Log Viewer (page d'accueil)
Route::get('/', [LogViewerController::class, 'index']);
Route::post('/logs/clear', [LogViewerController::class, 'clear'])->name('logs.clear');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

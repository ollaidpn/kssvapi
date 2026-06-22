<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $client;
    public Order $order;
    public int $itemsCount;
    public string $paymentMethod;
    public string $frontendUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $client, Order $order, int $itemsCount, string $paymentMethod, ?string $frontendUrl = null)
    {
        $this->client = $client;
        $this->order = $order;
        $this->itemsCount = $itemsCount;
        $this->paymentMethod = $paymentMethod;
        $this->frontendUrl = $frontendUrl ?? $order->frontend_url ?? config('app.frontend_website_endpoint', config('app.frontend_url', config('app.url')));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🛒 Nouvelle commande #{$this->order->reference}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new-order-alert',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabel(): string
    {
        return match($this->paymentMethod) {
            'cash_on_delivery' => 'Paiement à la livraison',
            'wave_senegal' => 'Wave',
            'orange_money_senegal' => 'Orange Money',
            default => $this->paymentMethod
        };
    }
}

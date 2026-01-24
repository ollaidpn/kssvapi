<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Order;
use App\Models\AppInfo;
use App\Helpers\Shortcut;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Order $order;
    public Collection $items;
    public bool $isPaid;
    public ?AppInfo $appInfo;
    public string $frontendUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Order $order, Collection $items, bool $isPaid = false, ?string $frontendUrl = null)
    {
        $this->user = $user;
        $this->order = $order;
        $this->items = $items;
        $this->isPaid = $isPaid;
        $this->appInfo = AppInfo::first();
        $this->frontendUrl = $frontendUrl ?? $order->frontend_url ?? config('app.frontend_url', config('app.url'));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->isPaid ? '✅ Confirmée et Payée' : '📦 Confirmée';
        return new Envelope(
            subject: "Commande {$this->order->reference} - {$status}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $admin;
    public string $activationToken;
    public string $frontendUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $admin, string $activationToken, ?string $frontendUrl = null)
    {
        $this->admin = $admin;
        $this->activationToken = $activationToken;
        $this->frontendUrl = $frontendUrl ?? config('app.frontend_website_endpoint', config('app.frontend_url', config('app.url')));
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Invitation Administrateur - KSSV',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

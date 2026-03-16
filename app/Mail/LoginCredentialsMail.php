<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $plainPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $plainPassword)
    {
        $this->user = $user;
        $this->plainPassword = $plainPassword;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-credentials',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

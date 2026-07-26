<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReengagementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Customer $customer, public readonly string $message) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We miss you!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'reengagement.email',
            with: ['customer' => $this->customer, 'message' => $this->message],
        );
    }
}

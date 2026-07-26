<?php

namespace App\Jobs;

use App\Mail\ReengagementMail;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the real re-engagement email (ARCHITECTURE.md §5.4) via the same
 * Mailtrap SMTP setup used for invoices — same queued-Job-around-a-Mailable
 * pattern as SendInvoiceEmailJob, so SMTP latency never blocks the request
 * that triggered the re-engagement.
 */
class SendReengagementEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Customer $customer, public readonly string $message) {}

    public function handle(): void
    {
        Mail::to($this->customer->email)->send(new ReengagementMail($this->customer, $this->message));
    }
}

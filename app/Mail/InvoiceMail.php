<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Support\InvoicePdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->fromAddress(),
            subject: __('Invoice :number from :company', [
                'number' => $this->invoice->number,
                'company' => config('invoicing.from.name'),
            ]),
        );
    }

    /**
     * Get the configured sender address.
     */
    private function fromAddress(): ?Address
    {
        $email = config('invoicing.from.email');

        return filled($email)
            ? new Address((string) $email, (string) config('invoicing.from.name'))
            : null;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice',
            with: [
                'invoice' => $this->invoice,
                'customer' => $this->invoice->customer,
                'from' => (array) config('invoicing.from'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = app(InvoicePdf::class);

        return [
            Attachment::fromData(
                fn (): string => $pdf->render($this->invoice)->output(),
                $pdf->filename($this->invoice),
            )->as('pdf'),
        ];
    }
}

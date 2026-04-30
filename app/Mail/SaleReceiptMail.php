<?php

namespace App\Mail;

use App\Models\PigCycleSale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SaleReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PigCycleSale $sale,
        public string $attachmentName,
        public string $attachmentData
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pig-Sikap Digital Sales Receipt #'.$this->sale->digital_receipt_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.sales.receipt',
            with: [
                'sale' => $this->sale,
                'associationName' => 'Elite Visionaries of Humayingan SLP Association',
            ],
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $this->attachmentData, $this->attachmentName)
                ->withMime('application/pdf'),
        ];
    }
}

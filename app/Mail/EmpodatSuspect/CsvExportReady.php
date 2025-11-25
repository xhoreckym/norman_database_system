<?php

declare(strict_types=1);

namespace App\Mail\EmpodatSuspect;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CsvExportReady extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The message content array
     */
    public array $messageContent;

    /**
     * Create a new message instance.
     */
    public function __construct(array $messageContent)
    {
        $this->messageContent = $messageContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = isset($this->messageContent['export_failed']) && $this->messageContent['export_failed']
            ? 'Empodat Suspect CSV Export Failed'
            : 'Empodat Suspect CSV Export Ready';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.empodatSuspectCsvReady',
            with: ['messageContent' => $this->messageContent]
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

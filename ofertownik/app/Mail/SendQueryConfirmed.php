<?php

namespace App\Mail;

use App\Models\Supervisor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendQueryConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public $supervisor;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public array $request_data,
        public Collection $cart,
        public Collection $files,
        public array $global_files,
    )
    {
        $this->request_data = $request_data;
        $this->cart = $cart;
        $this->files = $files;
        $this->global_files = $global_files;

        $this->supervisor = Supervisor::find($this->request_data["supervisor_id"]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Potwierdzenie wysłania zapytania",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.send-query-confirmed',
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

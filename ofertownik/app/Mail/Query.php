<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class Query extends Mailable
{
    use Queueable, SerializesModels;

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
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $client = $this->request_data["company_name"] ?? $this->request_data["client_name"];
        return new Envelope(
            subject: 'Wycena dla '.$client,
            replyTo: $this->request_data["email_address"],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.query',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // return array_map(fn ($file) => Attachment::fromStorage($file), $this->files->toArray());
        return [];
    }
}

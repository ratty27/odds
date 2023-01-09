<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthorizeMail extends Mailable
{
    use Queueable, SerializesModels;

    private $user_token;
    private $user_temp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token, $temp)
    {
        $this->user_token = $token;
        $this->user_temp = $temp;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: __("odds.email_authorize"),
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        $token = $this->user_token;
        $temp = $this->user_temp;
        return new Content(
            view: 'emails.authorize',
            with: compact('token', 'temp'),
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}

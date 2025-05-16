<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $device;
    public $ip;
    /**
     * Create a new message instance.
     */
    public function __construct($code, $device, $ip)
    {
        $this->code = $code;
        $this->device = $device;
        $this->ip = $ip;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Forgot Password',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.forgot_password',
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

    public function build()
    {
        return $this->subject('Your Verification Code')
                    ->view('emails.change_pin_code')
                    ->with(['code' => $this->code]);
    }
}

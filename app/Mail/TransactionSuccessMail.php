<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;
    public $title;
    public $heading;

    public function __construct($details, $title = 'Transaction Details', $heading = 'Your Transaction Details')
    {
        $this->details = $details;
        $this->title = $title;
        $this->heading = $heading;
    }

    public function build()
    {
        return $this->subject($this->title)
            ->view('emails.transaction_success')
            ->with([
                'details' => $this->details,
                'title' => $this->title,
                'heading' => $this->heading,
            ]);
    }
}
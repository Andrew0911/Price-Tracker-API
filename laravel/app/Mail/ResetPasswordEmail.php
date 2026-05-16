<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(private array $data) {}

    public function build()
    {
        return $this->subject('Reset your Price Pulse password')
            ->view('emails.reset-password', [
                'data' => $this->data,
            ]);
    }
}

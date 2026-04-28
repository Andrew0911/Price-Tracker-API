<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PriceAlertsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param User  $user
     * @param array $alerts  Array of ['product' => Product, 'alert' => PriceAlert, 'price' => float]
     */
    public function __construct(
        public readonly User $user,
        public readonly array $alerts,
    ) {}

    public function build(): static
    {
        $count = count($this->alerts);
        $subject = $count === 1
            ? 'Price drop alert — your target has been reached'
            : "Price drop alert — {$count} of your targets have been reached";

        return $this->subject($subject)
            ->view('emails.price-alerts', [
                'user' => $this->user,
                'alerts' => $this->alerts,
            ]);
    }
}

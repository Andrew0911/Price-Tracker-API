<?php

namespace App\Console\Commands;

use App\Mail\PriceAlertsMail;
use App\Models\PriceAlert;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mail;

class SendPriceAlertNotifications extends Command
{
    protected $signature = 'alerts:notify';
    protected $description = 'Send batched price alert emails, one email per user covering all triggered alerts';

    public function handle(): int
    {
        $now = Carbon::now();
        $dispatched = 0;

        // Get only the distinct user IDs that have pending alerts.
        $userIds = PriceAlert::query()
            ->whereNotNull('triggered_at')
            ->whereNull('notified_at')
            ->distinct()
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            Log::info('No pending price alerts.');
            return self::SUCCESS;
        }

        foreach ($userIds as $userId) {
            // Load only this user's alerts
            $alerts = PriceAlert::query()
                ->where('user_id', $userId)
                ->whereNotNull('triggered_at')
                ->whereNull('notified_at')
                ->with('product')
                ->get();

            if ($alerts->isEmpty()) {
                Log::info("No pending price alerts for userId {$userId}.");
                continue;
            }

            $alertsPayload = $alerts
                ->groupBy('product_id')
                ->map(function ($productAlerts) {
                    $alert = $productAlerts
                        ->firstWhere('type', PriceAlert::TYPE_TARGET_PRICE)
                        ?? $productAlerts->first();

                    return [
                        'product' => $alert->product,
                        'alert' => $alert,
                        'price' => (float) $alert->product->current_price,
                    ];
                })
                ->values()
                ->all();

            $user = User::find($userId);

            if(!$user) {
                continue;
            }

            Mail::to($user->email)
                ->locale(app()->getLocale())
                ->send(new PriceAlertsMail($user, $alertsPayload)->onQueue('emails'));

            // Mark as notified only after mail was sent
            $alerts->each(function (PriceAlert $alert) use ($now) {
                if ($alert->type === PriceAlert::TYPE_PRICE_DROP) {
                    $alert->update([
                        'triggered_at' => null,
                        'notified_at' => $now,
                    ]);

                    return;
                }

                $alert->update(['notified_at' => $now]);
            });

            $dispatched++;
        }

        Log::info("Dispatched alert emails for {$dispatched} user(s).");

        return self::SUCCESS;
    }
}

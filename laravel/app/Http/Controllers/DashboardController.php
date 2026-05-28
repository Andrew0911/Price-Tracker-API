<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $products = $user->products()->with('priceHistory')->get();

        $totalTracked = $products->count();
        $priceDrops = 0;
        $priceIncreases = 0;
        $activeAlerts = PriceAlert::where('user_id', $user->id)->whereNull('triggered_at')->count();
        $topPriceDrops = [];
        $topPriceIncreases = [];
        $storeBreakdown = [];

        foreach ($products as $product) {
            $store = $product->store ?: 'unknown';
            $storeBreakdown[$store] = ($storeBreakdown[$store] ?? 0) + 1;

            $history = $product->priceHistory->sortBy('checked_at')->values();

            if ($history->count() < 2) {
                continue;
            }

            $firstPrice = (float) $history->first()->price;
            $currentPrice = (float) $product->current_price;

            if ($currentPrice <= 0 || $firstPrice <= 0) {
                continue;
            }

            $diff = $currentPrice - $firstPrice;

            if ($diff < 0) {
                $priceDrops++;

                $dropAmount = abs($diff);
                $dropPercent = ($dropAmount / $firstPrice) * 100;

                $topPriceDrops[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'store' => $product->store,
                    'image_url' => $product->image_url,
                    'initial_price' => $firstPrice,
                    'current_price' => $currentPrice,
                    'currency' => $product->currency,
                    'drop_amount' => $dropAmount,
                    'drop_percent' => round($dropPercent, 2),
                ];
            } elseif ($diff > 0) {
                $priceIncreases++;

                $increaseAmount = abs($diff);
                $increasePercent = ($increaseAmount / $firstPrice) * 100;

                $topPriceIncreases[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'store' => $product->store,
                    'image_url' => $product->image_url,
                    'initial_price' => $firstPrice,
                    'current_price' => $currentPrice,
                    'currency' => $product->currency,
                    'increase_amount' => $increaseAmount,
                    'increase_percent' => round($increasePercent, 2),
                ];
            }
        }

        usort($topPriceDrops, fn ($a, $b) => $b['drop_amount'] <=> $a['drop_amount']);
        $topPriceDrops = array_slice($topPriceDrops, 0, 10);

        usort($topPriceIncreases, fn ($a, $b) => $b['increase_amount'] <=> $a['increase_amount']);
        $topPriceIncreases = array_slice($topPriceIncreases, 0, 10);

        arsort($storeBreakdown);
        $storeBreakdown = collect($storeBreakdown)
            ->map(fn ($count, $store) => [
                'store' => $store,
                'count' => $count,
            ])
            ->take(10)
            ->values();

        return $this->sendSuccessResponse(data: [
            'total_tracked' => $totalTracked,
            'price_drops' => $priceDrops,
            'price_increases' => $priceIncreases,
            'active_alerts' => $activeAlerts,
            'top_price_drops' => $topPriceDrops,
            'top_price_increases' => $topPriceIncreases,
            'store_breakdown' => $storeBreakdown,
        ]);
    }
}

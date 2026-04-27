<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
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
        $bestSaving = 0;

        foreach ($products as $product) {
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
                $saving = abs($diff);
                if ($saving > $bestSaving) {
                    $bestSaving = $saving;
                }
            } elseif ($diff > 0) {
                $priceIncreases++;
            }
        }

        return $this->sendSuccessResponse(data: [
            'total_tracked' => $totalTracked,
            'price_drops' => $priceDrops,
            'price_increases' => $priceIncreases,
            'best_saving' => round($bestSaving, 2),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Validate input dates if needed

        // Calculate order statistics using database queries
        $orderStats = DB::table('orders')
            ->select(
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(subtotal_price) as total_revenue'),
                DB::raw('SUM(CASE WHEN affiliates.id IS NOT NULL THEN subtotal_price * affiliates.commission_rate ELSE 0 END) as commission_owed')
            )
            ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
            ->leftJoin('affiliates', 'orders.affiliate_id', '=', 'affiliates.id')
            ->where('merchants.id', auth()->user()->merchant->id) // Assuming you have authentication and a merchant associated with the authenticated user
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->first();

        return response()->json($orderStats);
    }
}

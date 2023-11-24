<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        if (Order::where('order_id', $data['order_id'])->exists()) {
            return;
        }

        $merchant = Merchant::firstOrCreate(['domain' => $data['merchant_domain']]);

        $user = User::firstOrCreate(['email' => $data['customer_email']], ['name' => $data['customer_name']]);

        $affiliate = Affiliate::where('user_id', $user->id)->first();

        if (!$affiliate) {
            $affiliate = $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], 0.1); // Adjust commission rate as needed
        }

        Order::create([
            'order_id' => $data['order_id'],
            'subtotal_price' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'discount_code' => $data['discount_code'],
            'customer_email' => $data['customer_email'],
            'customer_name' => $data['customer_name'],
        ]);
    }
}

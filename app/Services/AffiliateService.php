<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        if (Affiliate::where('email', $email)->exists()) {
            throw new AffiliateCreateException('Affiliate with the same email already exists.');
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'user_type' => User::TYPE_MERCHANT,
        ]);

        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
        ]);

        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}

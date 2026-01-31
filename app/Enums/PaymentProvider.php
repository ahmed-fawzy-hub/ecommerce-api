<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

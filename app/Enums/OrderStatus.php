<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING='pending';
    case PAID='paid';
    case DELIVERED='delivered';
    case PROCESSING='processing';
    case SHIPPED='shipped';
    case CANCELLED='cancelled';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::PENDING => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::CANCELLED],
            self::DELIVERED => [],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED],
            self::CANCELLED => [],
        };
    }
    public function canTransitionTo(OrderStatus $targetStatus): bool
    {
        return in_array($targetStatus, $this->getAllowedTransitions());
    }
}
